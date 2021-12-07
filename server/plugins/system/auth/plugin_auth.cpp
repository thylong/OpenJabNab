#include <QDateTime>
#include <QStringList>
#include <QRandomGenerator>

#include "plugin_auth.h"
#include "account.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "packets/messagepacket.h"
#include "iq.h"
#include "log.h"
#include "settings.h"
#include "xmpphandler.h"
#include "translator.h"

PluginAuth::PluginAuth():PluginAuthInterface("auth", "Manage Authentication process")
{
	minBootcode = GetSettings("Bootcode", 0).toInt();
	badBootcodes = GetSettings("Bad", QStringList()).toStringList();
	currentId = 1;
}

const QHash<QString, QString> PluginAuth::GetChangelog(void)
{
	QHash<QString, QString> revisions;
	revisions.insert("1.0.1", "Add reboot bunny on low bootcode");
	revisions.insert("1.0.2", "Add reboot bunny on buggy bootcode");
	revisions.insert("1.0.3", "Update settings instantly");
	revisions.insert("1.1.0", "Identify bunny at first packet");
	return revisions;
}

void PluginAuth::OnBunnyConnect(Bunny * b)
{
	//LogDebug(QString("%1 just connect, boot is %2 (base %3). Minimum is %4, not compatibles : %5").arg(QString(b->GetID()), b->GetBootcode(), QString::number(b->GetBootcodeBase()), QString::number(minBootcode), badBootcodes.join(", ")));
	if(b->GetVersion() == 2)
	{
		if(b->GetBootcodeBase() < minBootcode)
		{
			LogDebug(QString("Rebooting %1 (bootcode %2 < OJN%3)").arg(QString(b->GetID()), b->GetBootcode(), QString::number(minBootcode)));
			b->SendPacket(MessagePacket("RB\n"), "auth");
		}
		else if(badBootcodes.contains(QString::number(b->GetBootcodeBase())))
		{
			LogDebug(QString("Rebooting %1 (bootcode %2 is buggy)").arg(QString(b->GetID()), b->GetBootcode()));
			b->SendPacket(MessagePacket("RB\n"), "auth");
		}
	}
}

// Helpers
#include <QCryptographicHash>
#define MD5(x) QCryptographicHash::hash(x, QCryptographicHash::Md5)
#define MD5_HEX(x) QCryptographicHash::hash(x, QCryptographicHash::Md5).toHex()
static QByteArray ComputeResponse(QByteArray const& username, QByteArray const& password, QByteArray const& nonce, QByteArray const& cnonce, QByteArray const& nc, QByteArray const& digest_uri, QByteArray const& mode)
{
	QByteArray HA1 = MD5_HEX(MD5(username + "::" + password) + ":" + nonce + ":" + cnonce);
	QByteArray HA2 = MD5_HEX(mode + ":" + digest_uri);
	QByteArray response = MD5_HEX(HA1 + ":" + nonce + ":" + nc + ":" + cnonce + ":auth:" + HA2);
	return response;
}

static QByteArray ComputeXor(QByteArray const& v1, QByteArray const& v2)
{
	QByteArray t1 = QByteArray::fromHex(v1);
	QByteArray t2 = QByteArray::fromHex(v2);
	for(int i = 0; i < t1.size(); i++)
	{
		t1[i] = (char)t1[i] ^ (char)t2[i];
	}
	return t1.toHex();
}

bool PluginAuth::DoAuth(XmppHandler * xmpp, QByteArray const& data, Bunny ** pBunny, QByteArray & answer)
{
	switch(xmpp->currentAuthStep)
	{
		case 0:
			// We should receive <?xml version='1.0' encoding='UTF-8'?><stream:stream to='ojn.soete.org' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>"
			if(data.startsWith("<?xml version='1.0' encoding='UTF-8'?>"))
			{

				QRegExp rx("from=\"([^\"]*)\"");
				if (rx.indexIn(data) != -1)
				{
					QByteArray const username = rx.cap(1).toLatin1();
					Bunny * bunny = BunnyManager::GetBunny(username);
					*pBunny = bunny; // Auth OK, set current bunny
				}
				// Send an auth Request
				answer.append("<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' id='"+QString::number(currentId)+"' from='"+ xmpp->GetXmppDomain() + "' version='1.0' xml:lang='en'>");
				answer.append("<stream:features><mechanisms xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><mechanism>DIGEST-MD5</mechanism><mechanism>PLAIN</mechanism></mechanisms><register xmlns='http://violet.net/features/violet-register'/></stream:features>");
				currentId++;
				xmpp->currentAuthStep = 1;
				return true;
			}
			if(data.length())
			{
				LogError("Bad Auth Step 0, disconnect ("+QString(data)+")");
			}
			else
			{
				LogError("Bad Auth Step 0, disconnect");
			}
			return false;

		case 1:
			{
				// Bunny request a register <iq type='get' id='1'><query xmlns='violet:iq:register'/></iq>
				IQ iq(data);
				if(iq.IsValid() && iq.Type() == IQ::Iq_Get && iq.Content() == "<query xmlns='violet:iq:register'/>")
				{
					// Send the request
					answer = iq.Reply(IQ::Iq_Result, "from='" + xmpp->GetXmppDomain() + "' %1 %4", "<query xmlns='violet:iq:register'><instructions>Choose a username and password to register with this server</instructions><username/><password/></query>");
					xmpp->currentAuthStep = 100;
					return true;
				}
				// Bunny request an auth <auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>
				if(data.startsWith("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>"))
				{
					// Send a challenge
					// <challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>...</challenge>
					// <challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>nonce="random_number",qop="auth",charset=utf-8,algorithm=md5-sess</challenge>
					QByteArray nonce = QByteArray::number((unsigned int)QRandomGenerator::global()->generate());
					QByteArray challenge = "nonce=\"" + nonce + "\",qop=\"auth\",charset=utf-8,algorithm=md5-sess";
					answer.append("<challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>" + challenge.toBase64() + "</challenge>");
					xmpp->currentAuthStep = 2;
					return true;
				}
				LogError("Bad Auth Step 1, disconnect");
				LogError("Received : " + QString(data));
				return false;
			}

		case 2:
			{
				// We should receive <response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>...</response>
				QRegExp rx("<response[^>]*>(.*)</response>");
				if (rx.indexIn(data) != -1)
				{
					QByteArray authString = QByteArray::fromBase64(rx.cap(1).toLatin1()).replace((char)0, "");
					// authString is like : username="",nonce="",cnonce="",nc=,qop=auth,digest-uri="",response=,charset=utf-8
					// Parse values
					rx.setPattern("username=\"([^\"]*)\",nonce=\"([^\"]*)\",cnonce=\"([^\"]*)\",nc=([^,]*),qop=auth,digest-uri=\"([^\"]*)\",response=([^,]*),charset=utf-8");
					if(rx.indexIn(authString) != -1)
					{
						QByteArray const username = rx.cap(1).toLatin1();
						Bunny * bunny = BunnyManager::GetBunny(username);

						// Check if we want to bypass auth
						if(GlobalSettings::Get("Config/StandAloneAuthBypass", false) == true)
						{
							// Send success
							LogInfo("Sending success instead of password verification");
							answer.append("<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>");

							bunny->Authenticating();
							*pBunny = bunny; // Auth OK, set current bunny

							xmpp->currentAuthStep = 4;
							return true;
						}


						QByteArray const password = bunny->GetBunnyPassword();
						QByteArray const nonce = rx.cap(2).toLatin1();
						QByteArray const cnonce = rx.cap(3).toLatin1().append((char)0); // cnonce have a dummy \0 at his end :(
						QByteArray const nc = rx.cap(4).toLatin1();
						QByteArray const digest_uri = rx.cap(5).toLatin1();
						QByteArray const bunnyResponse = rx.cap(6).toLatin1();
						if(bunnyResponse == ComputeResponse(username, password, nonce, cnonce, nc, digest_uri, "AUTHENTICATE"))
						{
							// Send challenge back
							// <challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>...</challenge>
							// rspauth=...
							QByteArray const rspAuth = "rspauth=" + ComputeResponse(username, password, nonce, cnonce, nc, digest_uri, "");
							answer.append("<challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>" + rspAuth.toBase64() + "</challenge>");

							bunny->Authenticating();
							*pBunny = bunny; // Auth OK, set current bunny

							xmpp->currentAuthStep = 3;
							return true;
						}

						LogError(QString("Authentication failure for bunny: %1").arg(QString(username)));
						// Bad password, send failure and restart auth
						answer.append("<failure xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><not-authorized/></failure>");
						xmpp->currentAuthStep = 0;
						return true;
					}
				}
				LogError("Bad Auth Step 2, disconnect");
				LogError("Received : " + QString(data));
				return false;
			}

		case 3:
			// We should receive <response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>
			if(data.startsWith("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>"))
			{
				// Send success
				answer.append("<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>");
				xmpp->currentAuthStep = 4;
				return true;
			}
			LogError("Bad Auth Step 3, disconnect");
			LogError("Received : " + QString(data));
			return false;

		case 4:
			// We should receive <?xml version='1.0' encoding='UTF-8'?>
			if(data.startsWith("<?xml version='1.0' encoding='UTF-8'?>"))
			{
				// Send success
				answer.append("<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' id='1331400675' from='"+ xmpp->GetXmppDomain() +"' version='1.0' xml:lang='en'>");
				answer.append("<stream:features><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><required/></bind><unbind xmlns='urn:ietf:params:xml:ns:xmpp-bind'/><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></stream:features>");
				xmpp->currentAuthStep = 0;
				(*pBunny)->Authenticated();
				(*pBunny)->SetXmppHandler(xmpp);

				(*pBunny)->SetGlobalSetting("LastIP", xmpp->GetBunnyIp());
				(*pBunny)->RemoveGlobalSetting("Last PingConnection");
				if(QString((*pBunny)->GetID()).startsWith("000e")) {
					(*pBunny)->SetVersion(3);
				} else {
					(*pBunny)->SetVersion(2);
				}
				// Bunny is now authenticated
				return true;
			}
			LogError("Bad Auth Step 4, disconnect");
			LogError("Received : " + QString(data));
			return false;


		case 100: // Register Bunny
			{
				// We should receive <iq to='xmpp.nabaztag.com' type='set' id='2'><query xmlns="violet:iq:register"><username>0019db01dbd7</username><password>208e6d83bfb2</password></query></iq>
				IQ iqAuth(data);
				if(iqAuth.IsValid() && iqAuth.Type() == IQ::Iq_Set)
				{
					QByteArray content = iqAuth.Content();
					QRegExp rx("<query xmlns=\"violet:iq:register\"><username>([0-9a-f]*)</username><password>([0-9a-f]*)</password></query>");
					if(rx.indexIn(content) != -1)
					{
						QByteArray user = rx.cap(1).toLatin1();
						QByteArray password = rx.cap(2).toLatin1();
						Bunny * bunny = BunnyManager::GetBunny(user);
						if(bunny->SetBunnyPassword(ComputeXor(user,password)))
						{
							LogError(QString("Setting password (%1) for bunny : %2").arg(QString(password), QString(user)));
							answer.append(iqAuth.Reply(IQ::Iq_Result, "%1 %2 %3 %4", content));
							xmpp->currentAuthStep = 1;
							return true;
						}
						LogError(QString("Password already set for bunny : ").append(QString(user)));
						return false;
					}
				}
				LogError("Bad Register, disconnect");
				return false;
			}

		default:
			LogError("Unknown Auth Step, disconnect");
			return false;
	}
}

bool PluginAuth::HttpRequestHandle(HTTPRequest & request)
{
	QString uri = request.GetURI();
	if (uri.startsWith("/vl/sendMailXMPP.jsp"))
	{
		QString mac = request.GetArg("m");
		Bunny * b = BunnyManager::GetBunny(this, mac.toLatin1());
		b->ClearBunnyPassword();
		LogError("Bunny just call sendMailXMPP, password reset");
		return true;
	}
	return false;
}

/*******/
/* API */
/*******/
void PluginAuth::InitApiCalls()
{
	DECLARE_PLUGIN_API_CALL("setAuthMethod(name)", &PluginAuth::Api_SelectAuth);
	DECLARE_PLUGIN_API_CALL("getListOfAuthMethods()", &PluginAuth::Api_GetListOfAuths);
	DECLARE_PLUGIN_API_CALL("config()", &PluginAuth::Api_Config);
}

PLUGIN_API_CALL(PluginAuth::Api_SelectAuth)
{
	Q_UNUSED(hRequest);
	Q_UNUSED(account);

	return new ApiAnswers::Error(QString("This API is deprecated"));
}

PLUGIN_API_CALL(PluginAuth::Api_GetListOfAuths)
{
	Q_UNUSED(hRequest);
	Q_UNUSED(account);

	return new ApiAnswers::Error(QString("This API is deprecated"));
}

PLUGIN_API_CALL(PluginAuth::Api_Config)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "bootcode")
	{
		if(hRequest.HasArg("set"))
		{
			minBootcode = hRequest.GetArg("set").toInt();
			SetSettings("Bootcode", minBootcode);
			return new ApiAnswers::Ok(Translator::tr("Minimum bootcode set to %1", account).arg(hRequest.GetArg("set")));
		}
		else
		{
			return new ApiAnswers::String(QString::number(GetSettings("Bootcode", 0).toInt()));
		}
	}
	if(action == "buggy")
	{
		if(hRequest.HasArg("add"))
		{
			QString bootcode = hRequest.GetArg("add");
			QStringList badBootcodes = GetSettings("Bad", QStringList()).toStringList();
			badBootcodes.append(bootcode);
			badBootcodes.removeDuplicates();
			SetSettings("Bad", badBootcodes);
			return new ApiAnswers::Ok(Translator::tr("Bootcode %1 added to buggy list", account).arg(hRequest.GetArg("add")));
		}
		else if(hRequest.HasArg("remove"))
		{
			QString bootcode = hRequest.GetArg("remove");
			QStringList badBootcodes = GetSettings("Bad", QStringList()).toStringList();
			badBootcodes.removeAll(bootcode);
			badBootcodes.removeDuplicates();
			SetSettings("Bad", badBootcodes);
			return new ApiAnswers::Ok(Translator::tr("Bootcode %1 removed from buggy list", account).arg(hRequest.GetArg("remove")));
		}
		else if(hRequest.HasArg("list"))
		{
			return new ApiAnswers::List(GetSettings("Bad", QStringList()).toStringList());
		}
		else
		{
			return new ApiAnswers::String(QString::number(GetSettings("Bootcode", 0).toInt()));
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
