#include <QDateTime>
#include <QHostAddress>
#include <QRegExp>



#include "bunny.h"
#include "bunnymanager.h"
#include "iq.h"
#include "log.h"
#include "messagepacket.h"
#include "pluginmanager.h"
#include "settings.h"
#include "ttsmanager.h"
#include "xmpphandler.h"

unsigned short XmppHandler::msgNb = 0;
unsigned short XmppHandler::msgStreamNb = 0;

#define DEFAULT_BIND_TIMEOUT_S 10
#define DEFAULT_XMPP_TIMEOUT_S 10

XmppHandler::XmppHandler(QTcpSocket * s)
	: pluginManager(PluginManager::Instance())
	, _lastMsgTime(std::chrono::system_clock::now())
{
	tempInXmppTraffic = 0;
	tempOutXmppTraffic = 0;

	incomingXmppSocket = s;
	bunny = 0;
	currentAuthStep = 0;
	streamingQueryCount = 0;

	tempMessage = QString();

	// Bunny -> OpenJabNab socket
	incomingXmppSocket->setParent(this);
	//QObject::connect(incomingXmppSocket, &QTcpSocket::disconnected, this, &XmppHandler::cleanup);
	QObject::connect(incomingXmppSocket, &QTcpSocket::readyRead, this, &XmppHandler::HandleBunnyXmppMessage);

	OjnXmppDomain = GlobalSettings::GetString("OpenJabNabServers/XmppServer").toLatin1();
	lastQueryResource = "streaming";
}

XmppHandler::~XmppHandler()
{
	//LogDebug(QString("Delete XMPPHandler 0x%1").arg((quintptr)this, QT_POINTER_SIZE * 2, 16, QChar('0')));
}

bool XmppHandler::shouldDelete(void)
{
	if(!incomingXmppSocket)
		return true;
	auto now = std::chrono::system_clock::now();
	if(bindingResource != "")
	{
		auto dt = std::chrono::duration_cast<std::chrono::seconds>(now - _lastBindTime).count();
		auto maxDt = GlobalSettings::GetInt("Timeout/Bind",DEFAULT_BIND_TIMEOUT_S);
		if(dt > maxDt)
		{
			if(bunny)
			{
				LogInfo("Bind process failed for " + bunny->GetBunnyName() + " ("+bunny->GetID()+")");
				bunny->SetXmppResource("idle");
			}
			else
				LogInfo("Bind process failed for unknow bunny");
				bindingResource.clear();
		}
	}
	auto dt = std::chrono::duration_cast<std::chrono::seconds>(now - _lastMsgTime).count();
	auto maxDt = GlobalSettings::GetInt("Timeout/Xmpp",DEFAULT_XMPP_TIMEOUT_S);
	if(dt > maxDt)
	{
		if(bunny)
			LogInfo("Xmpp timeout for " + bunny->GetBunnyName() + " ("+bunny->GetID()+")");
		cleanup();
		return true;
	}
	return false;
}

void XmppHandler::cleanup()
{
	if(incomingXmppSocket)
	{
		incomingXmppSocket->abort();
		if(bunny)
		{
			bunny->RemoveXmppHandler(this);
			bunny = nullptr;
		}
		incomingXmppSocket->deleteLater();
		incomingXmppSocket = nullptr;
		return;
	}

	deleteLater();
}

QString XmppHandler::GetBunnyIp()
{
	return bunny_real_ip != "" ? bunny_real_ip : incomingXmppSocket->peerAddress().toString();
}

void XmppHandler::HandleBunnyXmppMessage()
{
	_lastMsgTime = std::chrono::system_clock::now();
//	timeoutTimer->start(GlobalSettings::GetInt("Timeout/Xmpp",120)*1000);

	QByteArray data = incomingXmppSocket->readAll().trimmed();
	bool handled = false;
	bool known = false;
	bool temp = false;

	if(data != "")
	{
		if(bunny)
			LogDump(data, QString("XMPP from %1").arg(QString(bunny->GetID())));
		else
			LogDump(data, "XMPP from Bunny");
	}

	// If we don't know which bunny is connected, try to authenticate it
	if (!bunny || !bunny->IsAuthenticated())
	{
		tempInXmppTraffic += data.size();

		if(data.startsWith("PROXY"))
		{
			// PROXY TCP4 82.64.31.115 51.77.223.100 1531 5223
			auto split = data.split(' ');
			if(split.size() != 6)
				return;
			bunny_real_ip = split[2];
			return;
		}

		QByteArray ret;
		// Authentication error, disconnect
		if(pluginManager.GetAuthPlugin()->DoAuth(this, data, &bunny, ret) == false)
		{
			cleanup();
			return;
		}
		// Answer to bunny if needed
		if(!ret.isNull())
		{
			WriteToBunnyAndLog(ret);
			return;
		}
	}

	// No bunny yet
	if(!bunny)
	{
		LogError(QString("Unable to handle xmpp message : %1").arg(QString(data)));
		return;
	}

	if(tempMessage.length())
	{
		temp = true;
		if(data.length() > 0)
		{
			data = tempMessage.toLatin1() + data;
		}
	}

	if(data.length() > 0)
	{
		QRegExp rx("^<(message|iq|presence)[^>]*>");
		if(rx.indexIn(data) != -1)
		{
			QString opening = rx.cap(1);
			if(rx.setPattern("</" + opening + ">$"), rx.indexIn(data) == -1)
			{
				tempMessage = QString(data);
				temp = true;
			}
			else
			{
				temp = false;
			}
		}
	}
	// Save last bunny IP
	//bunny->SetGlobalSetting("LastIP", incomingXmppSocket->peerAddress().toString());

	if(!temp)
	{
		if(tempInXmppTraffic > 0)
		{
			bunny->AddInXmppTraffic(tempInXmppTraffic);
			tempInXmppTraffic = 0;
		}
		if(tempOutXmppTraffic > 0)
		{
			bunny->AddOutXmppTraffic(tempOutXmppTraffic);
			tempOutXmppTraffic = 0;
		}
		bunny->AddInXmppTraffic(data.size());

		tempMessage = QString();
		// Send raw xml info to all 'system' plugins and bunny's plugins
		handled = bunny->XmppBunnyMessage(data);

		if(!handled)
		{
			// Parse Bunny messages
			// Check if the data contains <message></message>
			QRegExp rx("<message[^>]*>(.*)</message>");
			if (rx.indexIn(data) != -1)
			{
				QString message = rx.cap(1);
				if (message.startsWith("<button"))
				{
					// Single Click : <button xmlns="violet:nabaztag:button"><clic>1</clic></button>
					// Double Click : <button xmlns="violet:nabaztag:button"><clic>2</clic></button>
					QRegExp rx("<clic>([0-9]+)</clic>");
					if (rx.indexIn(message) != -1)
					{
						known = true;
						int value = rx.cap(1).toInt();
						if (value == 1)
							handled = bunny->OnClick(PluginInterface::SingleClick);
						else if (value == 2)
							handled = bunny->OnClick(PluginInterface::DoubleClick);
						else
							LogWarning(QString("Unable to parse button/click message : %1").arg(QString(data)));
					}
					else
						LogWarning(QString("Unable to parse button message : %1").arg(QString(data)));
				}
				else if (message.startsWith("<ears"))
				{
					// <ears xmlns="violet:nabaztag:ears"><left>0</left><right>0</right></ears>
					QRegExp rx("<left>([0-9]+)</left><right>([0-9]+)</right>");
					if (rx.indexIn(message) != -1)
					{
						known = true;
						handled = bunny->OnEarsMove(rx.cap(1).toInt(), rx.cap(2).toInt());
						bunny->SetGlobalSetting("EarLeft", rx.cap(1));
						bunny->SetGlobalSetting("EarRight", rx.cap(2));
					}
					else
						LogWarning(QString("Unable to parse ears message : %1").arg(QString(data)));
				}
/*
				else if (message.startsWith("<sound"))
				{
					// <sound xmlns="violet:nabaztag:sound:idle"><volume>0</volume></sound>
					QRegExp rx("<volume>([0-9]+)</volume>");
					if (rx.indexIn(message) != -1)
					{
						known = true;
						handled = bunny->OnListen(rx.cap(1).toInt());
					}
					else
						LogWarning(QString("Unable to parse sound message : %1").arg(QString(data)));
				}
*/
				else if (!handled && !known)
					LogWarning(QString("Unknown message from bunny : %1").arg(QString(data)));
			}
			else if (rx.setPattern("<iq.*/iq>"), rx.indexIn(data) != -1)
			{
				IQ iq(data);
				if(iq.IsValid())
				{
					if(iq.Content() == "")
					{
						known = true;
					}
					else if(rx.setPattern("<bind[^>]*><resource>([^<]*)</resource></bind>"), rx.indexIn(iq.Content()) != -1)
					{
						bindingResource = rx.cap(1).toLatin1();
						bunny->SetXmppResource(bindingResource);

						//bindTimer->start(GlobalSettings::GetInt("Timeout/Bind")*1000);
						_lastBindTime = std::chrono::system_clock::now();

						QByteArray from = bunny->GetID()+"@"+OjnXmppDomain+"/"+bindingResource;
						WriteToBunnyAndLog(iq.Reply(IQ::Iq_Result, "%1 %4", "<bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><jid>"+from+"</jid></bind>"));
						handled = true;
						known = true;
					}
					else if(iq.Content() == "<ping xmlns='urn:xmpp:ping'/>")
					{
						WriteToBunnyAndLog(iq.Reply(IQ::Iq_Result, "%1 %4", QByteArray()));
						handled = true;
						known = true;
					}
					else if(iq.Content() == "<session xmlns='urn:ietf:params:xml:ns:xmpp-session'/>")
					{
						//bindTimer->start(GlobalSettings::GetInt("Timeout/Bind")*1000);
						_lastBindTime = std::chrono::system_clock::now();
						WriteToBunnyAndLog(iq.Reply(IQ::Iq_Result, "%4 %3 %2 %1", "<session xmlns='urn:ietf:params:xml:ns:xmpp-session'/>"));
						handled = true;
						known = true;
					}
					else if(iq.Content() == "<query xmlns=\"violet:iq:sources\"><packet xmlns=\"violet:packet\" format=\"1.0\"/></query>")
					{
						//bindTimer->start(GlobalSettings::GetInt("Timeout/Bind")*1000);
						_lastBindTime = std::chrono::system_clock::now();
						QByteArray status = bunny->GetInitPacket();
						WriteToBunnyAndLog(iq.Reply(IQ::Iq_Result, "%2 %3 %1 %4", "<query xmlns='violet:iq:sources'><packet xmlns='violet:packet' format='1.0' ttl='604800'>"+(status.toBase64())+"</packet></query>"));
						handled = true;
						known = true;
					}
					else if(rx.setPattern("<unbind[^>]*><resource>([^<]*)</resource></unbind>"), rx.indexIn(iq.Content()) != -1)
					{
						if(rx.cap(1) == "boot")
						{
							// Boot process finished
							bunny->Ready();
							bindingResource.clear();
						}
						WriteToBunnyAndLog(iq.Reply(IQ::Iq_Result, "%1 %4", QByteArray()));
						handled = true;
						known = true;
					}
					else if(rx.setPattern("<query xmlns='jabber:iq:version'><name>Nabaztag/tag</name><version>.*</version></query>"), rx.indexIn(iq.Content()) != -1)
					{
						if(rx.setPattern("from='"+bunny->GetID()+"@"+OjnXmppDomain+"/([^']+)'"), rx.indexIn(data) != -1)
						{
							QByteArray resource = rx.cap(1).toLatin1();
							bunny->SetXmppResource(resource);
						}
						handled = true;
						known = true;
					}
					else if (!handled && !known)
					{
						LogError(QString("Unknown IQ : %1").arg(QString(iq.Content())));
					}
				}
				else
				{
					LogError(QString("Invalid IQ : %1").arg(QString(data)));
				}
			}
			else if(rx.setPattern("<presence from='(.*)' id='(.*)'></presence>"), rx.indexIn(data) != -1)
			{
				//bindTimer->start(GlobalSettings::GetInt("Timeout/Bind")*1000);
				_lastBindTime = std::chrono::system_clock::now();
				
				QByteArray from = rx.cap(1).toLatin1();
				QByteArray id = rx.cap(2).toLatin1();
				WriteToBunnyAndLog("<presence from='"+from+"' to='"+from+"' id='"+id+"'/>");
				handled = true;
				known = true;
			}
			else if(data.length() == 0)
			{
				// Bunny's ping packet, nothing to do
				handled = true;
				known = true;

				if(bunny->GetXmppResource() == "streaming")
				{
					if(streamingQueryCount-- <= 0)
					{
						QByteArray ret = "<iq type='get' from='server@"+OjnXmppDomain+"/idle' to='"+bunny->GetID()+"@"+OjnXmppDomain+"/"+lastQueryResource+"' id='OJN-"+QByteArray::number(msgStreamNb)+"'><query xmlns='jabber:iq:version'/></iq>";
						lastQueryResource = lastQueryResource == "idle" ? "streaming" : "idle";
						WriteToBunnyAndLog(ret);
						msgStreamNb++;
						streamingQueryCount = 15;
					}
				}
			}
		}

		// If the message wasn't handled
		if (!handled && !known)
		{
			LogError(QString("Unable to handle bunny XMPP message : %1").arg(QString(data)));
		}
	}
}


void XmppHandler::WriteToBunny(QByteArray const& d)
{
	if(bunny)
	{
		bunny->AddOutXmppTraffic(QString(d).size());
	}
	else
	{
		tempOutXmppTraffic += QString(d).size();
	}
	incomingXmppSocket->write(d);
	incomingXmppSocket->flush();
}

void XmppHandler::WriteToBunnyAndLog(QByteArray const& d)
{
	if(bunny)
		LogDump(d, QString("XMPP to %1").arg(QString(bunny->GetID())));
	else
		LogDump(d, "XMPP to Bunny");
	WriteToBunny(d);
}

void XmppHandler::WriteExpertDataToBunny(QByteArray const& b)
{
	if(bunny)
	{
		LogDump(b, QString("XMPP to %1").arg(QString(bunny->GetID())));
		WriteToBunny(b);
		msgNb++;
	}
}

void XmppHandler::WriteDataToBunny(QByteArray const& b)
{
	if(bunny)
	{
		QByteArray msg;
		msg.append("<message from='net.openjabnab.platform@" + OjnXmppDomain + "/services' ");
		msg.append("to='" + bunny->GetID() + "@" + OjnXmppDomain + "/" + bunny->GetXmppResource() + "' ");
		msg.append("id='OJaNa-" + QByteArray::number(msgNb) + "'>");
		msg.append("<packet xmlns='violet:packet' format='1.0' ttl='604800'>");
		msg.append(b.toBase64());
		msg.append("</packet></message>");
		LogDump(msg, QString("XMPP to %1").arg(QString(bunny->GetID())));
		WriteToBunny(msg);
		msgNb++;
	}
}

QList<QByteArray> XmppHandler::XmlParse(QByteArray const& data)
{
	QList<QByteArray> list;
	msgQueue += data.trimmed();

	QRegExp rx;

	while(!msgQueue.isEmpty())
	{
		if (rx.setPattern("^(<\\?xml[^>]*\\?><stream:stream[^>]*>)"), rx.indexIn(msgQueue) != -1)
		{
			list << rx.cap(1).toLatin1();
			msgQueue.remove(0, rx.matchedLength());
		}
		else if (rx.setPattern("^(<[^>]*/>)"), rx.indexIn(msgQueue) != -1)
		{
			list << rx.cap(1).toLatin1();
			msgQueue.remove(0, rx.matchedLength());
		}
		else if (rx.setPattern("^(</[^>]*>)"), rx.indexIn(msgQueue) != -1) // Special case for </stream:stream>
		{
			list << rx.cap(1).toLatin1();
			msgQueue.remove(0, rx.matchedLength());
		}
		else
		{
			// Full xml message (<xxx>....</xxx>)
			// Find tag name
			rx.setPattern("^<([^ >]*)");
			if (rx.indexIn(msgQueue) == -1)
			{
				// Doesn't find the start tag... wait next message
				break;
			}
			QString tagName = rx.cap(1);
			// Search end tag
			rx.setPattern(QString("(.*</%1>)").arg(tagName));
			rx.setMinimal(true);
			if (rx.indexIn(msgQueue) == -1)
			{
				// Doesn't find the end tag... wait next message
				break;
			}
			list << rx.cap(1).toLatin1();
			msgQueue.remove(0, rx.matchedLength());
		}
	}
	return list;
}
