#include <QCoreApplication>
#include <QCryptographicHash>
#include <QUuid>
#include <QDateTime>
#include <QtSql/QtSql>
#include "QsLog.h"
#include "accountmanager.h"
#include "dbmanager.h"
#include "ambientpacket.h"
#include "chorconfigpacket.h"
#include "serviceconfigpacket.h"
#include "messagepacket.h"
#include "choregraphy.h"
#include "bunny.h"
#include "cron.h"
#include "log.h"
#include "httprequest.h"
#include "plugininterface.h"
#include "pluginmanager.h"
#include "sleeppacket.h"
#include "xmpphandler.h"
#include "account.h"
#include "translator.h"
#include "ttsmanager.h"
#include "settings.h"

#define SINGLE_CLICK_PLUGIN_SETTINGNAME "singleClickPlugin"
#define DOUBLE_CLICK_PLUGIN_SETTINGNAME "doubleClickPlugin"

Bunny::Bunny(QByteArray const& bunnyID)
{
	services.clear();
	leftEar = 0;
	rightEar = 0;

	// Init click plugins
	singleClickPlugin = NULL;
	doubleClickPlugin = NULL;

	needSave = false;
	id = bunnyID;
	state = State_Disconnected;
	xmppHandler = 0;
	apiChorCount = 0;
	apiSpecialCount = 0;

	LoadConfig();

	if(GetGlobalSetting("TimeZone","unset").toString() == "unset")
	        SetGlobalSetting("TimeZone", GlobalSettings::Get("Config/TimeZone", "UTC").toString());

	if(GetGlobalSetting("TimeZoneAlias","unset").toString() == "unset")
	{
		QString timezoneName = GetGlobalSetting("TimeZone","unset").toString();
		SetGlobalSetting("TimeZoneAlias", timezoneName);
	}

	saveTimer = new QTimer(this);
	connect(saveTimer, SIGNAL(timeout()), this, SLOT(SaveConfig()));
	saveTimer->start(15*60*1000); // 5min

	messages.clear();

	lastTTSTime = QDateTime::currentDateTime();

	trafficCount = GetGlobalSetting("trafficCount", 0).toInt();

	inXmppTraffic = GetGlobalSetting("inXmppTraffic", 0).toLongLong();
	outXmppTraffic = GetGlobalSetting("outXmppTraffic", 0).toLongLong();
	inHttpTraffic = GetGlobalSetting("inHttpTraffic", 0).toLongLong();
	outHttpTraffic = GetGlobalSetting("outHttpTraffic", 0).toLongLong();

	ClearSoundToSend();
}

void Bunny::AddSoundToSend(QString sound)
{
	soundToSend.append(sound);
}

void Bunny::ClearSoundToSend()
{
	soundToSend.clear();
}

QString Bunny::GetXmlVoiceList(QString language)
{
	QMap<QString, QVariant> list = TTSManager::GetVoiceList(language);
	QMapIterator<QString, QVariant> i(list);
	QStringList voices;
	while (i.hasNext())
	{
		i.next();
		foreach(QString voice, i.value().toString().split(","))
		{
			voices.append(i.key());
		}
	}
	QString s = "<voiceListTTS nb=\""+QString::number(voices.length())+"\"/>";
	foreach(QString voice, voices)
	{
		s+= "<voice lang=\"" + language + "\" command=\""+voice+"\"/>";
	}
	return s;
}

QString Bunny::GetXmlVoiceList()
{
	return GetXmlVoiceList(GetLanguage());
}

ApiManager::ApiAnswer * Bunny::ProcessVioletApiCall(HTTPRequest const& hRequest)
{
	ApiManager::ApiViolet* answer = new ApiManager::ApiViolet();

	if(hRequest.HasArg("sn") && hRequest.HasArg("token"))
	{
  		QString serial = hRequest.GetArg("sn").toLower();
	  	QString token = hRequest.GetArg("token");

		if(GetGlobalSetting("VApiEnable",false).toBool())
		{
			if((GetGlobalSetting("VApiToken","").toString() == token && serial.toLatin1()==GetID()) || GetGlobalSetting("VApiPublic",false).toBool())
       			{

	        	        //if(hRequest.GetURI().startsWith("/ojn/FR/api_stream.jsp"))
	        	        if(hRequest.GetURI().contains("/api_stream.jsp"))
		                {
					if(GetVersion() == 2)
					{
						if(IsConnected())
						{
							if(hRequest.HasArg("urlList"))
							{
								QByteArray message = ("ST " + hRequest.GetArg("urlList").split("|", QString::SkipEmptyParts).join("\nMW\nST ") + "\nMW\n").toLatin1();
								SendPacket(MessagePacket(message), "api_stream.jsp");
								answer->AddMessage("WEBRADIOSENT", "Your webradio has been sent");
							}
							else
							{
								answer->AddMessage("NOCORRECTPARAMETERS", "Please check urlList parameter !");
							}
						}
						else
						{
							answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
						}
					}
					else
					{
						answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
					}
		                }
        		        else if(hRequest.GetURI().contains("/api.jsp"))
	                	{
	                		AmbientPacket p;
        	        	        if(hRequest.HasArg("action")) // TODO: send good values
        		                {
	                	                switch(hRequest.GetArg("action").toInt())
	                        	        {
        	        	                        case 2:
        		                                        answer->AddXml("<listfriend nb=\"0\"/>");
	                	                                break;
                                	        	case 3:
                                		                answer->AddXml("<listreceivedmsg nb=\"0\"/>");
                        	                	        break;
	                	                        case 4:
        		                                        //answer->AddXml("<timezone>(GMT + 01:00) Bruxelles, Copenhague, Madrid, Paris</timezone>");
        		                                        answer->AddXml("<timezone>" + GetGlobalSetting("TimeZoneAlias",GetGlobalSetting("TimeZone","UTC").toString()).toString() + "</timezone>");
	        	                                        break;
                        		                case 6:
                	        	                        answer->AddXml("<blacklist nb=\"0\"/>");
        	                        	                break;
	                                        	case 7:
	                        	                        if(IsSleeping())
        	        	                                        answer->AddXml("<rabbitSleep>YES</rabbitSleep>");
        		                                        else
	                	                                        answer->AddXml("<rabbitSleep>NO</rabbitSleep>");
                        		                        break;
                	                	        case 8:
        	                                	        answer->AddXml("<rabbitVersion>V"+QString::number(GetVersion())+"</rabbitVersion>");
		                                               	break;
        	                                	case 9:
								if(hRequest.HasArg("lng"))
								{
                	                	                	answer->AddXml(GetXmlVoiceList(hRequest.GetArg("lng")));
								}
								else if(hRequest.HasArg("language"))
								{
                	                	                	answer->AddXml(GetXmlVoiceList(hRequest.GetArg("language")));
								}
								else
								{
                	                	                	answer->AddXml(GetXmlVoiceList());
								}
                        		                        break;
                	        	                case 10:
        	                        	                answer->AddXml("<rabbitName>" + GetBunnyName() + "</rabbitName>");
	                                        	        break;
	                                        	case 11:
        	                        	                answer->AddXml("<langListUser nb=\"1\"/><myLang lang=\"" + GetLanguage() + "\"/>");
                	        	                        break;
	                	                        case 12:
        		                                        answer->AddXml("<message>LINKPREVIEW</message><comment>XXXX</comment>");
	        	                                        break;
                        	        	        case 13:
								if(GetVersion() == 2)
								{
									if(IsConnected())
									{
										answer->AddXml("<message>COMMANDSENT</message><comment>Your rabbit will change status</comment>");
										SendPacket(SleepPacket(SleepPacket::Wake_Up), "api.jsp");
									}
									else
									{
										answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
									}
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
        	                                	        break;
		                                        case 14:
								if(GetVersion() == 2)
								{
									if(IsConnected())
									{
										answer->AddXml("<message>COMMANDSENT</message><comment>Your rabbit will change status</comment>");
										SendPacket(SleepPacket(SleepPacket::Sleep), "api.jsp");
									}
									else
									{
										answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
									}
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
								break;
	                                        	case 15:
	                        	                        if(IsConnected())
        	        	                                        answer->AddXml("<rabbitConnected>YES</rabbitConnected>");
        		                                        else
	                	                                        answer->AddXml("<rabbitConnected>NO</rabbitConnected>");
                        		                        break;
	                                        	case 16:
	                        	                        if(IsConnected())
        	        	                                        answer->AddXml("<lastOnline>"+QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm:ss")+"</lastOnline>");
        		                                        else
								{
									QString lastOnline = "Never connected";
									QDateTime date;
									if(GetVersion() == 2)
									{
										date = GetGlobalSetting("Last JabberConnection", QDateTime()).toDateTime();
									}
									else if(GetVersion() == 1)
									{
										date = GetGlobalSetting("Last Ping", QDateTime()).toDateTime();
									}
									if(date.isValid())
									{
										lastOnline = date.toString("yyyy-MM-dd hh:mm:ss");
									}
	                	                                        answer->AddXml("<lastOnline>"+lastOnline+"</lastOnline>");
								}
                        		                        break;
	                                        	case 17:
								if(GetVersion() == 2)
								{
									if(IsConnected())
									{
										answer->AddMessage("COMMANDSENT", "Bunny is going to reboot !");
										SendPacket(SleepPacket(SleepPacket::Sleep), "api.jsp");
										SendPacket(MessagePacket("RB\n"), "api.jsp");
									}
									else
									{
										answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
									}
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
                        		                        break;
	                                        	case 18:
								if(GetVersion() == 2)
								{
									if(IsConnected())
									{
										answer->AddMessage("COMMANDSENT", "Bunny is going to restart !");
										xmppHandler->Disconnect();
										xmppHandler = 0;
									}
									else
									{
										answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
									}
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
                        		                        break;
	                                        	case 19:
								if(GetVersion() == 2)
								{
									if(IsConnected())
									{
										PluginInterface * plugin = PluginManager::Instance().GetPluginByName("record");
										if(plugin != NULL)
										{
											QStringList records;
											int offset = 0;
											if(hRequest.HasArg("offset"))
											{
												offset = hRequest.GetArg("offset").toInt();
											}
											int limit = 20;
											if(hRequest.HasArg("limit"))
											{
												limit = hRequest.GetArg("limit").toInt();
											}
											QMetaObject::invokeMethod(plugin, "GetRecordList", Q_RETURN_ARG(QStringList, records), Q_ARG(Bunny*, this), Q_ARG(int, offset), Q_ARG(int, limit));
											QString s = "<recordList nb=\""+QString::number(records.length())+"\" offset=\""+QString::number(offset)+"\" limit=\""+QString::number(limit)+"\">";
											foreach(QString record, records)
											{
												s+= "<record file=\"" + record + "\"/>";
											}
											s += "</recordList>";
											answer->AddXml(s);
										}
										else
										{
											answer->AddMessage("NOSUCHPLUGIN", "Can't find record plugin");
										}
									}
									else
									{
										answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
									}
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
                        		                        break;
        	                	                default:
		                                                break;
        	                        	}
               			        }
	        	                else if(hRequest.HasArg("plugin"))
               			        {
						if(IsConnected())
						{
							if(GlobalSettings::Get("Config/HttpVioletApiExtended", false).toBool() && hRequest.HasArg("plugin"))
							{
								if(!IsLimited() || GetApiSpecialCount() <= 5)
								{
									AddApiSpecial();
									PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("plugin"));
									if(plugin != NULL)
									{
										if(plugin->SupportExtendedApi())
										{
											if(HasPlugin(plugin))
											{
												if(hRequest.HasArg("function"))
												{
													if(plugin->GetExtendedApiFunctions().contains(hRequest.GetArg("function")))
													{
														QString type = plugin->GetExtendedApiFunctions().value(hRequest.GetArg("function"));
														QString param = "";
														if(hRequest.HasArg("arg"))
														{
															param = hRequest.GetArg("arg");
														}
														QString function = "OnApi" + hRequest.GetArg("function").replace(0, 1, hRequest.GetArg("function").left(1).toUpper());
														QString retour = "";
														QMetaObject::invokeMethod(plugin, function.toStdString().c_str(), Q_RETURN_ARG(QString, retour), Q_ARG(Bunny*, this), Q_ARG(QVariant, param));
														if(type == "string")
														{
															answer->AddXml("<message>COMMANDSENT</message><comment>"+retour+"</comment>");
														}
														else if(type == "xml")
														{
															answer->AddXml(retour);
														}
														else
														{
															answer->AddXml("<message>COMMANDSENT</message>");
														}
													}
													else
													{
														answer->AddMessage("BADAPICALL", "This function does not exist");
													}
												}
												else
												{
													answer->AddMessage("PARTIALCALL", "You have to specify which function to use");
												}
											}
											else
											{
												answer->AddMessage("PLUGINNOTAVAILABLE", "This plugin is not associated with the bunny");
											}
										}
										else
										{
											answer->AddMessage("EXTENDEDAPINOTSUPPORTED", "Extended API not available for this plugin");
										}
									}
									else
									{
										answer->AddMessage("UNKNOWPLUGIN", "This plugin does not exist");
									}
								}
								else
								{
									answer->AddMessage("PREMIUM_ONLY", "Extended API limited to 5 calls a day for not premium users");
								}
							}
							else
							{
								answer->AddMessage("EXTENDEDAPINOTAVAILABLE", "Extended API not available");
							}
						}
						else
						{
                	        			answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
						}
					}
	        	                else
               			        {
						if(IsConnected())
						{
							int autochor = -2;
							if(hRequest.HasArg("idmessage"))
							{
								answer->AddMessage("MESSAGESENT", "Your message has been sent");
							}
							if(hRequest.HasArg("posleft") || hRequest.HasArg("posright"))
							{
								int left = 0;
								int right = 0;
								if(hRequest.HasArg("posleft")) left = hRequest.GetArg("posleft").toInt();
								if(hRequest.HasArg("posright")) right = hRequest.GetArg("posright").toInt();
								if(left >= 0 && left <= 16 && right >= 0 && right <= 16)
								{
									answer->AddMessage("EARPOSITIONSENT", "Your ears command has been sent");
									if(GetVersion() == 2)
									{
										p.SetEarsPosition(left, right);
									}
									else
									{
										SetService(LeftEar, left);
										SetService(RightEar, right);
									}
									SetGlobalSetting("EarLeft", left);
									SetGlobalSetting("EarRight", right);
								}
								else
								{
									answer->AddMessage("EARPOSITIONNOTSENT", "Your ears command could not be sent");
								}
							}
							int volume = 0;
							if(hRequest.HasArg("volume"))
							{
								volume = hRequest.GetArg("volume").toInt();
							}
							QString language = GetLanguage();
							if(hRequest.HasArg("lng"))
							{
								language = hRequest.GetArg("lng");
							}
							if(hRequest.HasArg("language"))
							{
								language = hRequest.GetArg("language");
							}
							QString voice = GetVoice();
							if(hRequest.HasArg("voice"))
							{
								voice = hRequest.GetArg("voice");
							}
							if(hRequest.HasArg("autochor"))
							{
								if(hRequest.GetArg("autochor").toInt() == -1)
								{
									autochor = qrand() % 8;
								}
								else if(hRequest.GetArg("autochor").toInt() >= 0)
								{
									autochor = hRequest.GetArg("autochor").toInt() % 8;
								}
							}
							if(hRequest.HasArg("tts") && hRequest.GetArg("tts").length() > 0)
							{
								QDateTime now = QDateTime::currentDateTime();
								if(lastTTSTime.addSecs(1) > now)
								{
									answer->AddMessage("TTSNOTSENT", "Another TTS is in progress");
								}
								else
								{
									QString text = TTSManager::trim(hRequest.GetArg("tts"));
									if(text != lastTTS || lastTTSTime.addSecs(10) < now)
									{
										//TTSLog::Log(GetID(), "Bunny", hRequest.GetArg("tts"));
										TTSManager::OutputFormat format = GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
										TTSAnswer sound = TTSManager::CreateSound(text, voice, language, format, false);
										QsLogging::Logger::TTSLog(GetID(), "Bunny", sound);
										if(GetVersion() == 1)
										{
											AddSoundToSend(sound.file);
										}
										else
										{
											QByteArray msg = "MU " + sound.file.toLatin1() + "\n" + (autochor != -2 ? "PL " + QString::number(autochor).toLatin1() : "") + "MW\n";
											if(volume > 0)
											{
												volume = 255 - volume;
												if(volume <= 0)
												{
													volume = 1;
												}
												msg = "TV " + QString::number(volume).toLatin1() + "\n" + msg + "RV\n";
											}
											SendPacket(MessagePacket(msg), "api.jsp");
										}
										answer->AddMessage("TTSSENT", "Your text has been sent");
										lastTTSTime = now;
										lastTTS = text;
									}
									else
									{
										answer->AddMessage("TTSNOTSENT", "Same TTS less than 10s ago");
									}
								}
							}
							if(hRequest.HasArg("callurl"))
							{
								if(GetVersion() == 2)
								{
									SendPacket(MessagePacket(QString("CU "+hRequest.GetArg("callurl")+"\nMW\n").toLatin1()), "api.jsp");
									answer->AddMessage("CALLURLSENT", "Your url has been sent");
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
							}
							if(hRequest.HasArg("urllist"))
							{
								if(GetVersion() == 2)
								{
									QByteArray message = ("ST " + hRequest.GetArg("urllist").split("|", QString::SkipEmptyParts).join("\nMW\nST ") + "\nMW\n").toLatin1();
									SendPacket(MessagePacket(message), "api.jsp");
									answer->AddMessage("WEBRADIOSENT", "Your webradio has been sent");
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
							}
							if(hRequest.HasArg("ears"))
							{
								answer->AddEarPosition(GetGlobalSetting("EarLeft", 0).toInt(), GetGlobalSetting("EarRight", 0).toInt()); // TODO: send real positions
							}
							if(hRequest.HasArg("chor"))
							{
								if(GetVersion() == 2)
								{
									if(GetApiChorCount() <= 720)
									{
										AddApiChor();
										Choregraphy c;
										if(c.Parse(hRequest.GetArg("chor"))) //TODO: Check for good chor
										{
											QDir chorFolder = QDir(GlobalSettings::GetString("Config/RealHttpRoot"));
											if (!chorFolder.cd("chor"))
											{
												if (!chorFolder.mkdir("chor"))
												{
													LogError(QString("Unable to create 'chor' directory !\n"));
													answer->AddMessage("CHORNOTSENT", "Your chor could not be sent (can't create folder)");
												}
												chorFolder.cd("chor");
											}
											QString fileName = QCryptographicHash::hash(c.GetData(), QCryptographicHash::Md5).toHex().append(".chor");
											QString filePath = chorFolder.absoluteFilePath(fileName);

											QFile file(filePath);
											if (!file.open(QIODevice::WriteOnly))
											{
												LogError("Cannot open chor file for writing");
												answer->AddMessage("CHORNOTSENT", "Your chor could not be sent (error in file)");
											}
											else
											{
												file.write(c.GetData());
												file.close();
												SendPacket(MessagePacket(("CH broadcast/ojn_local/chor/" + fileName + "\n").toLatin1()), "api.jsp");
												answer->AddMessage("CHORSENT", "Your chor has been sent");
											}
										}
										else
										{
											//LogInfo("Not sending chor to bunny " + QString(GetID()) + ", too many in 24h");
											answer->AddMessage("CHORNOTSENT", "Your chor could not be sent (bad chor)");
										}
									}
									else
									{
										answer->AddMessage("CHORNOTSENT", "Your chor could not be sent (too many chor in 24h)");
									}
								}
								else
								{
									answer->AddMessage("NOTAVAILABLE", "Not available for this bunny version");
								}
							}
						}
						else
						{
                	        			answer->AddMessage("NOTCONNECTED", "Bunny is not connected !");
						}
                        		}
					if(p.GetServices().count() > 0)
					{
						if(GetVersion() == 2)
						{
	        	        			SendPacket(p, "api.jsp");
						}
					}
                		}
				else
				{
					answer->AddMessage("BADAPICALL", "Your API call is wrong");
				}
		        }
		        else
		        {
		                answer->AddMessage("NOGOODTOKENORSERIAL", "Your token or serial number are not correct !");
        		}
		}
		else
		{
			answer->AddMessage("APIDISABLED", "API is disabled for this bunny");
		}
	}
	else
	{
		answer->AddMessage("APIDISABLED", "Missing serial or token");
        }
	return answer;
}

Bunny::~Bunny()
{
	SaveConfig();
}

QString Bunny::CheckPlugin(PluginInterface * plugin, bool isAssociated)
{
	if(!plugin)
		return QString("Unknown plugin : %1");

	if(!(plugin->GetType() & PluginInterface::BunnyV1Plugin) && !(plugin->GetType() & PluginInterface::BunnyV2Plugin))
		return QString("Bad plugin type : %1");

	if(!plugin->GetEnable())
		return QString("Plugin '%1' is globally disabled");

	if(isAssociated && (!listOfPluginsPtr.contains(plugin)))
		return QString("Plugin '%1' is not associated with this bunny");

	return QString();
}

void Bunny::LoadConfig()
{
        QSqlDatabase db = DbManager::getDb();
	bool close = DbManager::openDbIfNeeded();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT mac, settings FROM bunny WHERE mac=:mac");
	query->bindValue(":mac", GetID());
	query->exec();
	if(query->size() == 1)
	{
		query->first();
		QDataStream stream(query->value(1).toByteArray());
		stream.setVersion(QDataStream::Qt_4_3);
		stream >> GlobalSettings >> PluginsSettings >> listOfPlugins >> knownRFIDTags;
		if (stream.status() != QDataStream::Ok)
		{
			LogWarning(QString("Problem when loading settings for bunny : %1").arg(QString(id.toHex())));
		}
	        apiChorCount = GlobalSettings.value("apiChorCount", 0).toInt();
	        lastApiChorCount = GlobalSettings.value("lastApiChorCount", 0).toDateTime();

/*
		stream >> messages;
		if (stream.status() == QDataStream::ReadPastEnd)
		{
			messages.clear();
		}
*/
		// "Load" associated bunny plugins
		QStringList obsoleteList = PluginManager::Instance().GetObsoleteList();
		foreach(QString s, listOfPlugins)
		{
			PluginInterface * p = PluginManager::Instance().GetPluginByName(s);
			if(p)
			{
				if(p->GetEnable())
				{
					if(p->GetType() & PluginInterface::BunnyV1Plugin || p->GetType() & PluginInterface::BunnyV2Plugin)
					{
						listOfPluginsPtr.append(p);
					}
					else
					{
						LogError(QString("Bunny %1 has bad type (%2), removing !").arg(QString(GetID()), s));
						//listOfPlugins.removeAll(s);
						//needSave = true;
					}
				}
				else
				{
					//LogWarning(QString("Bunny %1 : '%2' is globally disabled !").arg(QString(GetID()), s));
				}
			}
			else
			{
				if(obsoleteList.contains(s))
				{
					LogError(QString("Bunny %1 has invalid plugin (%2), removing !").arg(QString(GetID()), s));
					PluginsSettings.remove(s);
					listOfPlugins.removeAll(s);
					needSave = true;
				}
				else
				{
					//LogError(QString("Bunny %1 has invalid plugin (%2)").arg(QString(GetID()), s));
				}
			}
		}

		// Load single/doubleClickPlugin preferences
		if(GlobalSettings.contains(SINGLE_CLICK_PLUGIN_SETTINGNAME))
		{
			QString pluginName = GlobalSettings.value(SINGLE_CLICK_PLUGIN_SETTINGNAME).toString();
			PluginInterface * plugin = PluginManager::Instance().GetPluginByName(pluginName);
			if(plugin)
			{
				QString error = CheckPlugin(plugin, true);
				if(error.isNull())
					singleClickPlugin = plugin;
				else
				{
					singleClickPlugin = NULL;
					GlobalSettings.remove(SINGLE_CLICK_PLUGIN_SETTINGNAME);
					LogError(error.arg(pluginName));
					needSave = true;
				}
			}
			else
			{
				LogError(QString("Bunny %1 has invalid click plugin (%2), removing !").arg(QString(GetID()), pluginName));
				singleClickPlugin = NULL;
				GlobalSettings.remove(SINGLE_CLICK_PLUGIN_SETTINGNAME);
				PluginsSettings.remove(pluginName);
				listOfPlugins.removeAll(pluginName);
				needSave = true;
			}
		}
		else
		{
			singleClickPlugin = NULL;
		}
		if(GlobalSettings.contains(DOUBLE_CLICK_PLUGIN_SETTINGNAME))
		{
			QString pluginName = GlobalSettings.value(DOUBLE_CLICK_PLUGIN_SETTINGNAME).toString();
			PluginInterface * plugin = PluginManager::Instance().GetPluginByName(pluginName);
			if(plugin)
			{
				QString error = CheckPlugin(plugin, true);
				if(error.isNull())
					doubleClickPlugin = plugin;
				else
				{
					doubleClickPlugin = NULL;
					GlobalSettings.remove(DOUBLE_CLICK_PLUGIN_SETTINGNAME);
					LogError(error.arg(pluginName));
					needSave = true;
				}
			}
			else
			{
				LogError(QString("Bunny %1 has invalid click plugin (%2), removing !").arg(QString(GetID()), pluginName));
				doubleClickPlugin = NULL;
				GlobalSettings.remove(DOUBLE_CLICK_PLUGIN_SETTINGNAME);
				PluginsSettings.remove(pluginName);
				listOfPlugins.removeAll(pluginName);
				needSave = true;
			}
		}
		else
		{
			doubleClickPlugin = NULL;
		}
	}
	query->finish();
	delete query;
	if(close)
		DbManager::releaseDb();

}

void Bunny::SaveConfig()
{
	//Log::LogInfo("SaveBunny " + GetBunnyName());
	if(trafficCount & 1) // Xmpp
	{
		unsigned long long _inXmppTraffic = GetGlobalSetting("inXmppTraffic", 0).toLongLong();
		unsigned long long _outXmppTraffic = GetGlobalSetting("outXmppTraffic", 0).toLongLong();
		if(_inXmppTraffic != inXmppTraffic || _outXmppTraffic != outXmppTraffic)
		{
			SetGlobalSetting("inXmppTraffic", inXmppTraffic);
			SetGlobalSetting("outXmppTraffic", outXmppTraffic);
		}
	}
	if(trafficCount & 2) // Http
	{
		unsigned long long _inHttpTraffic = GetGlobalSetting("inHttpTraffic", 0).toLongLong();
		unsigned long long _outHttpTraffic = GetGlobalSetting("outHttpTraffic", 0).toLongLong();
		if(_inHttpTraffic != inHttpTraffic || _outHttpTraffic != outHttpTraffic)
		{
			SetGlobalSetting("inHttpTraffic", inHttpTraffic);
			SetGlobalSetting("outHttpTraffic", outHttpTraffic);
		}
	}
	if(needSave)
	{
		//Log::LogInfo("Really SaveBunny " + GetBunnyName());
	        GlobalSettings.insert("apiChorCount", apiChorCount);
	        GlobalSettings.insert("lastApiChorCount", lastApiChorCount);

		QByteArray settings;
		QDataStream out(&settings, QIODevice::WriteOnly);
		out.setVersion(QDataStream::Qt_4_3);
		out << GlobalSettings << PluginsSettings << listOfPlugins << knownRFIDTags;// << messages;

        	QSqlDatabase db = DbManager::getDb();
		bool close = DbManager::openDbIfNeeded();
		QSqlQuery *query = new QSqlQuery(db);
		query->prepare("INSERT INTO bunny SET `mac`=:mac, `settings`=:settings, `server_id`=:server, `account_id`=(SELECT `id` FROM account WHERE `username`=:username) ON DUPLICATE KEY UPDATE `settings`=:settings_up, `server_id`=:server_up, `account_id`=(SELECT `id` FROM account WHERE `username`=:username_up)");
		query->bindValue(":mac", GetID());
		query->bindValue(":username", GetGlobalSetting("OwnerAccount"));
		query->bindValue(":username_up", GetGlobalSetting("OwnerAccount"));
		query->bindValue(":settings", settings);
		query->bindValue(":settings_up", settings);
		query->bindValue(":server", GlobalSettings::GetInt("Database/ServerId"));
		query->bindValue(":server_up", GlobalSettings::GetString("Database/ServerId"));
		bool ret = query->exec();
		if(!ret)
		{
			LogError(QString("Impossible to save bunny in DB : %1").arg(query->lastError().driverText()));
		}
		else
		{
			needSave = false;
		}
		delete query;
		if(close)
			DbManager::releaseDb();
	}
	//Log::LogInfo("End SaveBunny " + GetBunnyName());
}

void Bunny::SetXmppHandler(XmppHandler * x)
{
	xmppHandler = x;
}

void Bunny::RemoveXmppHandler(XmppHandler * x)
{
	if (xmppHandler == x)
	{
		xmppHandler = 0;
		state = State_Disconnected;
		SetGlobalSetting("Last JabberDisconnection", QDateTime::currentDateTime());
		LogInfo(QString("%1 (%2) quit the server").arg(GetBunnyName(), QString(GetID())));
		OnDisconnect();
	}
}

// Called when the bunny start an authenticating process
void Bunny::Authenticating()
{
	if(xmppHandler)
	{
		xmppHandler->Disconnect();
		xmppHandler = 0;
	}
	state = State_Authenticating;
}

// Called when the bunny succeed an auth
void Bunny::Authenticated()
{
	state = State_Authenticated;
	SetGlobalSetting("Last JabberConnection", QDateTime::currentDateTime());
}

// Called when the bunny is ready (auth/boot finished)
void Bunny::Ready()
{
	state = State_Ready;
	OnConnect();
}

// Called when the bunny is requesting init packet (during boot)
QByteArray Bunny::GetInitPacket() const
{
	// Create minimal packet
	AmbientPacket a(AmbientPacket::Service_Nose, AmbientPacket::Nose_No);
	a.SetEarsPosition(0,0);

	SleepPacket s(SleepPacket::Wake_Up);

	// Pass to system plugins
	PluginManager::Instance().OnInitPacket(this,a,s);

	// Pass AmbientPacket to all bunny's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnInitPacket(this, a, s);
	}

	// Create packetList and return packet's data
	QList<Packet *> l;
	l.append(&a);
	l.append(&s);

	return Packet::GetData(l);
}

void Bunny::SendPacket(Packet const& p)
{
	SendPacket(p, "");
}

void Bunny::SendPacket(Packet const& p, QString sender)
{
	if (
		xmppHandler && (p.GetType() != Packet::Packet_Message
		|| (!IsSleeping()
		|| GetGlobalSetting("Insomniac",false).toBool())))
	{
		if(p.GetType() == Packet::Packet_Message)
		{
			MessagePacket * m = new MessagePacket(p.GetPrintableData());
			// Send to all 'system' plugins
			PluginManager::Instance().BeforeSendMessage(this, m, sender);

			// And all bunny's plugins
			foreach(PluginInterface * plugin, listOfPluginsPtr)
			{
				if(plugin->GetEnable())
					plugin->BeforeSendMessage(this, m, sender);
			}
			xmppHandler->WriteDataToBunny(m->GetData());
			QsLogging::Logger::DumpLog(m->GetPrintableData(), "XMPP MsgPacket");
			delete m;
		}
		else if(p.GetType() == Packet::Packet_Serviceconfig)
		{
			QsLogging::Logger::DumpLog(p.GetPrintableData(), "XMPP SrvPacket");
			xmppHandler->WriteDataToBunny(p.GetData());
		}
		else if(p.GetType() == Packet::Packet_Chorconfig)
		{
			QsLogging::Logger::DumpLog(p.GetPrintableData(), "XMPP ChorPacket");
			xmppHandler->WriteDataToBunny(p.GetData());
		}
		else
		{
			QsLogging::Logger::DumpLog(p.GetPrintableData(), "XMPP Packet");
			xmppHandler->WriteDataToBunny(p.GetData());
		}
	}
}

void Bunny::SendExpertData(QByteArray const& b)
{
	if (xmppHandler)
	{
		QsLogging::Logger::DumpLog(b, "XMPP SendExpertData");
		xmppHandler->WriteExpertDataToBunny(b);
	}
}

void Bunny::SendData(QByteArray const& b)
{
	if (xmppHandler)
	{
		QsLogging::Logger::DumpLog(b.toHex(), "XMPP SendData");
		xmppHandler->WriteDataToBunny(b);
	}
}

QVariant Bunny::GetGlobalSetting(QString const& key, QVariant const& defaultValue) const
{
	if (GlobalSettings.contains(key))
	{
		return GlobalSettings.value(key);
	}
	return defaultValue;
}

void Bunny::SetGlobalSetting(QString const& key, QVariant const& value)
{
	needSave = true;
	GlobalSettings.insert(key, value);
}

void Bunny::RemoveGlobalSetting(QString const& key)
{
	needSave = true;
	GlobalSettings.remove(key);
}

void Bunny::CleanSettings()
{
	GlobalSettings.clear();
	PluginsSettings.clear();
}

QVariant Bunny::GetPluginSetting(QString const& pluginName, QString const& key, QVariant const& defaultValue) const
{
	if (PluginsSettings.contains(pluginName))
	{
		if(PluginsSettings[pluginName].contains(key))
			return PluginsSettings[pluginName].value(key);
		else
			return defaultValue;
	}
	else
		return defaultValue;
}

QStringList Bunny::GetPluginSettings(QString const& pluginName) const
{
	return (QStringList)PluginsSettings[pluginName].keys();

}

void Bunny::SetPluginSetting(QString const& pluginName, QString const& key, QVariant const& value)
{
	needSave = true;
	PluginsSettings[pluginName].insert(key, value);
}

void Bunny::RemovePluginSetting(QString const& pluginName, QString const& key)
{
	needSave = true;
	PluginsSettings[pluginName].remove(key);
}

QHash<QString, QVariant> Bunny::ExportPluginSettings(QString const& pluginName)
{
	return PluginsSettings[pluginName];
}

void Bunny::ImportPluginSettings(QString const& pluginName, QHash<QString, QVariant> conf)
{
	needSave = true;
	PluginsSettings.insert(pluginName, conf);
}

// API Add plugin to this bunny
void Bunny::AddPlugin(PluginInterface * p)
{
	if(!listOfPlugins.contains(p->GetName()))
	{
		listOfPlugins.append(p->GetName());
		listOfPluginsPtr.append(p);
		needSave = true;
		if(IsConnected())
			p->OnBunnyConnect(this);
		SaveConfig();
	}
	if(GlobalSettings.contains(SINGLE_CLICK_PLUGIN_SETTINGNAME))
	{
		QString pluginName = GlobalSettings.value(SINGLE_CLICK_PLUGIN_SETTINGNAME).toString();
		if(p->GetName() == pluginName)
		{
			QString error = CheckPlugin(p, true);
			if(error.isNull())
				singleClickPlugin = p;
			else
			{
				singleClickPlugin = NULL;
				GlobalSettings.remove(SINGLE_CLICK_PLUGIN_SETTINGNAME);
				LogError(error.arg(pluginName));
			}
		}
	}
	else
	{
		singleClickPlugin = NULL;
	}
	if(GlobalSettings.contains(DOUBLE_CLICK_PLUGIN_SETTINGNAME))
	{
		QString pluginName = GlobalSettings.value(DOUBLE_CLICK_PLUGIN_SETTINGNAME).toString();
		if(p->GetName() == pluginName)
		{
			QString error = CheckPlugin(p, true);
			if(error.isNull())
				doubleClickPlugin = p;
			else
			{
				doubleClickPlugin = NULL;
				GlobalSettings.remove(DOUBLE_CLICK_PLUGIN_SETTINGNAME);
				LogError(error.arg(pluginName));
			}
		}
	}
	else
	{
		doubleClickPlugin = NULL;
	}
}

// API Remove plugin to this bunny
void Bunny::RemovePlugin(PluginInterface * p)
{
	if(listOfPlugins.contains(p->GetName()))
	{
		if(p == singleClickPlugin)
		{
			singleClickPlugin = NULL;
		}
		if(p == doubleClickPlugin)
		{
			doubleClickPlugin = NULL;
		}
		listOfPlugins.removeAll(p->GetName());
		listOfPluginsPtr.removeAll(p);
		needSave = true;
		if(IsConnected())
			p->OnBunnyDisconnect(this);
		SaveConfig();
	}
}

// Global plugin enable/disable
void Bunny::PluginStateChanged(PluginInterface * p)
{
	if(listOfPluginsPtr.contains(p) && IsConnected())
	{
		if(p->GetEnable())
			p->OnBunnyConnect(this);
		else
			p->OnBunnyDisconnect(this);
	}
}

// New plugin loaded
void Bunny::PluginLoaded(PluginInterface * p)
{
	if(listOfPlugins.contains(p->GetName()))
	{
		listOfPluginsPtr.append(p);
		if(p->GetEnable())
			p->OnBunnyConnect(this);
	}
}

// Plugin unloaded
void Bunny::PluginUnloaded(PluginInterface * p)
{
	if(listOfPluginsPtr.contains(p))
	{
		listOfPluginsPtr.removeAll(p);
		if(p->GetEnable())
			p->OnBunnyDisconnect(this);
	}
}

// Bunny is connected
void Bunny::OnConnect()
{
	// Send to all 'system' plugins
	PluginManager::Instance().OnBunnyConnect(this);

	// And all bunny's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnBunnyConnect(this);
	}
}

// Bunny is gone away
void Bunny::OnDisconnect()
{
	// Send to all 'system' plugins
	PluginManager::Instance().OnBunnyDisconnect(this);

	// And all bunny's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnBunnyDisconnect(this);
	}
	SaveConfig();
}

// Bunny V1 is connected
void Bunny::OnNewPing()
{
	// Send to all 'system' plugins
	PluginManager::Instance().OnBunnyConnect(this);

	// And all bunny's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnBunnyConnect(this);
	}
}

// Bunny V1 is gone away
void Bunny::OnNoPing()
{
	// Send to all 'system' plugins
	PluginManager::Instance().OnBunnyDisconnect(this);

	// And all bunny's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnBunnyDisconnect(this);
	}
	SaveConfig();
}

// Received XMPP Message
bool Bunny::XmppBunnyMessage(QByteArray const& data)
{
	bool handled = false;
	// Send to all 'system' plugins
	handled = PluginManager::Instance().XmppBunnyMessage(this, data);

	// And all bunny's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			handled |= p->XmppBunnyMessage(this, data);
	}
	return handled;
}

// Called when top button is pushed
bool Bunny::OnClick(PluginInterface::ClickType type)
{
	LogInfo(QString("%1 click on %2, %3").arg(type == PluginInterface::SingleClick ? "Single" : "Double", GetBunnyName(), QString(GetID())));
	if(PluginManager::Instance().OnClick(this, type))
		return true;

	// Check if registeredClickPlugin is available
	if(type == PluginInterface::SingleClick && singleClickPlugin)
	{
		return singleClickPlugin->OnClick(this, type);
	}
	if(type == PluginInterface::DoubleClick && doubleClickPlugin)
	{
		return doubleClickPlugin->OnClick(this, type);
	}
/*
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
		{
			if(p->Click(this, type))
				return true;
		}
	}
*/
	return false;
}

// Called when ears was moded
bool Bunny::OnEarsMove(int left, int right)
{
	if(PluginManager::Instance().OnEarsMove(this, left, right))
		return true;

	// Call OnClick for all 'bunny' plugins until one returns true
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
		{
			if(p->OnEarsMove(this, left, right))
				return true;
		}
	}
	return false;
}

// Called when bunny listen
bool Bunny::OnListen(int volume)
{
	if(PluginManager::Instance().OnListen(this, volume))
		return true;

	// Call OnClick for all 'bunny' plugins until one returns true
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
		{
			if(p->OnListen(this, volume))
				return true;
		}
	}
	return false;
}

// Called when a RFID Tad was read
bool Bunny::OnRFID(QByteArray const& tag)
{
	//if(this != NULL)
	{
		if(!knownRFIDTags.contains(tag))
			knownRFIDTags.insert(tag, QString());

		if(PluginManager::Instance().OnRFID(this, tag))
			return true;

		// Call OnClick for all 'system' plugins until one returns true
		foreach(PluginInterface * p, listOfPluginsPtr)
		{
			if(p->GetEnable())
			{
				if(p->OnRFID(this, tag))
					return true;
			}
		}
	}
	return false;
}

// Called when a sound was recorded and decoded for voice command
bool Bunny::OnVoiceCommand(QString const& command, QStringList const& otherCmds)
{
	LogDebug("Voice command : " + command + " for " + GetBunnyName());

	if(PluginManager::Instance().OnVoiceCommand(this, command, otherCmds))
		return true;

	// Call OnClick for all 'system' plugins until one returns true
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
		{
			if(p->OnVoiceCommand(this, command, otherCmds))
				return true;
		}
	}

	return false;
}

// Called when a sound was recorded
bool Bunny::OnRecord(QString const& filename)
{
	//if(this != NULL)
	{
		LogDebug("Record " + filename + " for " + GetBunnyName());

		if(PluginManager::Instance().OnRecord(this, filename))
			return true;

		// Call OnClick for all 'system' plugins until one returns true
		foreach(PluginInterface * p, listOfPluginsPtr)
		{
			if(p->GetEnable())
			{
				if(p->OnRecord(this, filename))
					return true;
			}
		}

		if(PluginManager::Instance().OnRecord(this, filename, false))
			return true;
	}
	return false;
}


QDir * Bunny::GetUserDir()
{
	QString accountName = QCryptographicHash::hash(GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QDir userDir(GlobalSettings::GetString("Config/RealHttpRoot"));
	if (!userDir.cd("users"))
	{
		if (!userDir.mkdir("users"))
		{
			LogError(QString("Unable to create users directory !\n"));
		}
		userDir.cd("users");
	}
	if (!userDir.cd(accountName))
	{
		if (!userDir.mkdir(accountName))
		{
			LogError(QString("Unable to create " + accountName + " directory !\n"));
		}
		userDir.cd(accountName);
	}
	QStringList filters;
	filters << "*.mp3";
	userDir.setNameFilters(filters);
	return new QDir(userDir);
}

QByteArray Bunny::GetBroadcastHTTPUserPath(QString f)
{
	QString userName = QCryptographicHash::hash(GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QString userFolder = QString("%1/%2/%3").arg(GlobalSettings::GetString("Config/HttpRoot"), GlobalSettings::GetString("Config/HttpUsersFolder"), userName);
	return QString("broadcast/%1/%2").arg(userFolder, f).toLatin1();
}

/*******/
/* API */
/*******/

void Bunny::InitApiCalls()
{
	DECLARE_API_CALL("registerPlugin(name)", &Bunny::Api_AddPlugin);
	DECLARE_API_CALL("unregisterPlugin(name)", &Bunny::Api_RemovePlugin);
	DECLARE_API_CALL("getListOfActivePlugins()", &Bunny::Api_GetListOfAssociatedPlugins);

	DECLARE_API_CALL("setTimezone(name)", &Bunny::Api_SetTimeZone);
	DECLARE_API_CALL("getTimezone()", &Bunny::Api_GetTimeZone);

	DECLARE_API_CALL("setSingleClickPlugin(name)", &Bunny::Api_SetSingleClickPlugin);
	DECLARE_API_CALL("setDoubleClickPlugin(name)", &Bunny::Api_SetDoubleClickPlugin);
	DECLARE_API_CALL("getClickPlugins()", &Bunny::Api_GetClickPlugins);

	DECLARE_API_CALL("getListOfKnownRFIDTags()", &Bunny::Api_GetListOfKnownRFIDTags);
	DECLARE_API_CALL("setRFIDTagName(tag,name)", &Bunny::Api_SetRFIDTagName);

	DECLARE_API_CALL("setBunnyName(name)", &Bunny::Api_SetBunnyName);

	DECLARE_API_CALL("setService(service,value)", &Bunny::Api_SetService);

  DECLARE_API_CALL("deletePluginSettings(plugin)", &Bunny::Api_DeletePluginSettings);

	DECLARE_API_CALL("resetPassword()", &Bunny::Api_ResetPassword);
	DECLARE_API_CALL("resetOwner()", &Bunny::Api_ResetOwner);
	DECLARE_API_CALL("getOwner()", &Bunny::Api_GetOwner);

	DECLARE_API_CALL("disconnect()", &Bunny::Api_Disconnect);

        DECLARE_API_CALL("setInsomniac(insomniac)", &Bunny::Api_setInsomniac);
        DECLARE_API_CALL("getInsomniac()", &Bunny::Api_getInsomniac);

        DECLARE_API_CALL("setPublicVAPI(public)", &Bunny::Api_setPublicVApi);
        DECLARE_API_CALL("getPublicVAPI()", &Bunny::Api_getPublicVApi);
	DECLARE_API_CALL("enableVAPI()", &Bunny::Api_enableVApi);
	DECLARE_API_CALL("disableVAPI()", &Bunny::Api_disableVApi);
	DECLARE_API_CALL("getVAPIStatus()", &Bunny::Api_getVApiStatus);
	DECLARE_API_CALL("getVAPIToken()", &Bunny::Api_getVApiToken);
	DECLARE_API_CALL("setVAPIToken(tk)", &Bunny::Api_setVApiToken);

	DECLARE_API_CALL("getlast(param)", &Bunny::Api_getOneLast);
	DECLARE_API_CALL("getlasts()", &Bunny::Api_getAllLast);

	DECLARE_API_CALL("getbootcode()", &Bunny::Api_getBootcode);
	DECLARE_API_CALL("getversion()", &Bunny::Api_getVersion);
	DECLARE_API_CALL("setversion(version)", &Bunny::Api_setVersion);

	DECLARE_API_CALL("getlanguage()", &Bunny::Api_getLanguage);
	DECLARE_API_CALL("setlanguage(lng)", &Bunny::Api_setLanguage);

	DECLARE_API_CALL("getallcrons()", &Bunny::Api_getAllCronList);
	DECLARE_API_CALL("getnextcrons()", &Bunny::Api_getNextCronList);

	DECLARE_API_CALL("voice()", &Bunny::Api_Voice);

	DECLARE_API_CALL("resource()", &Bunny::Api_Resource);
	DECLARE_API_CALL("traffic()", &Bunny::Api_Traffic);
}

API_CALL(Bunny::Api_DeletePluginSettings)
{
if(!hRequest.HasArg("plugin"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("plugin"));

	QString plugin = hRequest.GetArg("plugin");
	needSave = true;
	PluginsSettings.remove(plugin);
  return new ApiManager::ApiOk(Translator::tr("Deleted all settings for plugin '%1'").arg(plugin));
}

API_CALL(Bunny::Api_Resource)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "get")
	{
		return new ApiManager::ApiString(QString(GetXmppResource()));
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("resource"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("resource"));

		QString resource = hRequest.GetArg("resource");

		SetXmppResource(resource.toLatin1());
		return new ApiManager::ApiOk(Translator::tr("Bunny is now '%1'", account).arg(resource));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(Bunny::Api_Traffic)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "config")
	{
		if(hRequest.HasArg("set"))
		{
			trafficCount = hRequest.GetArg("set").toInt();
			SetGlobalSetting("trafficCount", trafficCount);
			return new ApiManager::ApiOk(Translator::tr("Traffic count is now '%1'", account).arg(trafficCount));
		}
		else
		{
			return new ApiManager::ApiString(QString::number(trafficCount));
		}
	}
	else if(action == "get")
	{
		QString traffic = "<traffic>";
		traffic += "<inXmpp>"+QString::number(inXmppTraffic)+"</inXmpp>";
		traffic += "<inHttp>"+QString::number(inHttpTraffic)+"</inHttp>";
		traffic += "<outXmpp>"+QString::number(outXmppTraffic)+"</outXmpp>";
		traffic += "<outHttp>"+QString::number(outHttpTraffic)+"</outHttp>";
		traffic += "</traffic>";
		return new ApiManager::ApiXml(traffic);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(Bunny::Api_Voice)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(TTSManager::GetVoiceList(GetLanguage(), account.IsPremium() || account.IsAdmin()));
	}
	else if(action == "test")
	{
		if(!hRequest.HasArg("voice"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("voice"));

		QString voice = hRequest.GetArg("voice");

		if(!hRequest.HasArg("sentence"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("sentence"));

		QString sentence = hRequest.GetArg("sentence");

		TTSAnswer sound = TTSManager::CreateSound(sentence, voice, "");
		return new ApiManager::ApiString(sound.file);
	}
	else if(action == "get")
	{
		return new ApiManager::ApiString(GetGlobalSetting("Voice", QString()).toString());
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("voice"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("voice"));

		QString voice = hRequest.GetArg("voice");

		SetGlobalSetting("Voice", voice);
		return new ApiManager::ApiOk(Translator::tr("Bunny will now use '%1' voice when possible", account).arg(voice));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(Bunny::Api_AddPlugin)
{
	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("name"));

	QString error = CheckPlugin(plugin);
	if(!error.isNull())
		return new ApiManager::ApiError(error.arg(hRequest.GetArg("name")));

	AddPlugin(plugin);
	return new ApiManager::ApiOk(Translator::tr("Added '%1' as active plugin", account).arg(Translator::tr(plugin->GetVisualName(), account)));
}

API_CALL(Bunny::Api_RemovePlugin)
{
	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("name"));
	QString error = CheckPlugin(plugin);
	if(!error.isNull())
		return new ApiManager::ApiError(error.arg(hRequest.GetArg("name")));

	RemovePlugin(plugin);
	return new ApiManager::ApiOk(Translator::tr("Removed '%1' as active plugin", account).arg(Translator::tr(plugin->GetVisualName(), account)));
}

API_CALL(Bunny::Api_GetListOfAssociatedPlugins)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QString> list;
	foreach (PluginInterface * p, listOfPluginsPtr)
		list.append(p->GetName());

	return new ApiManager::ApiList(list);

}

API_CALL(Bunny::Api_SetSingleClickPlugin)
{
	Q_UNUSED(account);

	if(hRequest.GetArg("name") == "none")
	{
		RemoveGlobalSetting(SINGLE_CLICK_PLUGIN_SETTINGNAME);
		singleClickPlugin = NULL;
		return new ApiManager::ApiOk(Translator::tr("Removed preferred single click plugin", account));
	}

	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("name"));

	QString error = CheckPlugin(plugin, true);
	if(!error.isNull())
		return new ApiManager::ApiError(error.arg(hRequest.GetArg("name")));

	singleClickPlugin = plugin;
	SetGlobalSetting(SINGLE_CLICK_PLUGIN_SETTINGNAME, plugin->GetName());
	return new ApiManager::ApiOk(Translator::tr("Set '%1' as single click plugin", account).arg(plugin->GetVisualName()));
}

API_CALL(Bunny::Api_SetDoubleClickPlugin)
{
	Q_UNUSED(account);

	if(hRequest.GetArg("name") == "none")
	{
		RemoveGlobalSetting(DOUBLE_CLICK_PLUGIN_SETTINGNAME);
		doubleClickPlugin = NULL;
		return new ApiManager::ApiOk(Translator::tr("Removed preferred double click plugin", account));
	}

	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("name"));

	QString error = CheckPlugin(plugin, true);
	if(!error.isNull())
		return new ApiManager::ApiError(error.arg(hRequest.GetArg("name")));

	doubleClickPlugin = plugin;
	SetGlobalSetting(DOUBLE_CLICK_PLUGIN_SETTINGNAME, plugin->GetName());
	return new ApiManager::ApiOk(Translator::tr("Set '%1' as double click plugin", account).arg(plugin->GetVisualName()));
}

API_CALL(Bunny::Api_GetClickPlugins)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QString> list;
	list.append(GetGlobalSetting(SINGLE_CLICK_PLUGIN_SETTINGNAME, QString()).toString());
	list.append(GetGlobalSetting(DOUBLE_CLICK_PLUGIN_SETTINGNAME, QString()).toString());

	return new ApiManager::ApiList(list);
}

API_CALL(Bunny::Api_GetListOfKnownRFIDTags)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list;

	QHash<QByteArray, QString>::const_iterator i;
	for (i = knownRFIDTags.constBegin(); i != knownRFIDTags.constEnd(); ++i)
		list.insert(QString(i.key()), i.value());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(Bunny::Api_SetRFIDTagName)
{
	QByteArray tagName = hRequest.GetArg("tag").toLatin1();
	if(!knownRFIDTags.contains(tagName))
		return new ApiManager::ApiError(Translator::tr("Tag '%1' is unkown", account).arg(hRequest.GetArg("tag")));

	knownRFIDTags[tagName] = hRequest.GetArg("name");
	needSave = true;

	return new ApiManager::ApiOk(Translator::tr("Name '%1' associated to tag '%2'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("tag")));
}

API_CALL(Bunny::Api_SetBunnyName)
{
	SetBunnyName( hRequest.GetArg("name") );

	return new ApiManager::ApiOk(Translator::tr("Bunny '%1' is now named '%2'", account).arg(GetID(), hRequest.GetArg("name")));
}

API_CALL(Bunny::Api_SetService)
{
	int service = hRequest.GetArg("service").toInt();
	int value = hRequest.GetArg("value").toInt();

	AmbientPacket a((AmbientPacket::Services)service, value);
	SendPacket(a, "bunny");

	return new ApiManager::ApiOk(Translator::tr("Set value '%2' for service '%1'", account).arg(QString::number(service), QString::number(value)));
}

API_CALL(Bunny::Api_ResetPassword)
{
	Q_UNUSED(hRequest);

	ClearBunnyPassword();
	return new ApiManager::ApiOk(Translator::tr("Password cleared", account));
}

API_CALL(Bunny::Api_ResetOwner)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account * a = AccountManager::GetAccountByLogin(GetGlobalSetting("OwnerAccount").toByteArray());
	if(a)
	{
		a->RemoveBunny(GetID());
	}
	RemoveGlobalSetting("OwnerAccount");
	return new ApiManager::ApiOk(Translator::tr("Owner cleared", account));
}

API_CALL(Bunny::Api_GetOwner)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	return new ApiManager::ApiString(GetGlobalSetting("OwnerAccount").toString());
}

API_CALL(Bunny::Api_Disconnect)
{
	Q_UNUSED(account);

        if(!xmppHandler)
		return new ApiManager::ApiError(Translator::tr("Bunny is not connected", account));

	if(hRequest.HasArg("reboot"))
	{
		SendPacket(MessagePacket("RB\n"), "bunny");
		return new ApiManager::ApiOk(Translator::tr("Bunny is restarting", account));
	}
	else
        {
                xmppHandler->Disconnect();
                xmppHandler = 0;
		return new ApiManager::ApiOk(Translator::tr("Connexion closed", account));
        }
}

API_CALL(Bunny::Api_SetTimeZone)
{
        QString tzN = hRequest.GetArg("name");
        QString tz = hRequest.GetArg("alias");
        SetGlobalSetting("TimeZone", tzN);
        SetGlobalSetting("TimeZoneAlias", tz);
	OnDisconnect();
	OnConnect();
        return new ApiManager::ApiOk(Translator::tr("Bunny is now in %1 timezone", account).arg(tzN));
}

API_CALL(Bunny::Api_GetTimeZone)
{
        Q_UNUSED(account);
        Q_UNUSED(hRequest);
        QString tz = GetGlobalSetting("TimeZone","UTC").toString();
        QString tzN = GetGlobalSetting("TimeZoneAlias",tz).toString();
        return new ApiManager::ApiString(tz + ";" + tzN);
}

API_CALL(Bunny::Api_setInsomniac)
{
        bool i = (bool)(hRequest.GetArg("insomniac").toInt());
        SetGlobalSetting("Insomniac",i);
        QString night = i ? Translator::tr("Bunny is now insomniac", account) : Translator::tr("Bunny is now a good sleeper", account);
        return new ApiManager::ApiOk(night);
}

API_CALL(Bunny::Api_getInsomniac)
{
        Q_UNUSED(account);
        Q_UNUSED(hRequest);
        QString night = GetGlobalSetting("Insomniac",false).toBool() ? "insomniac" : "a good sleeper";
        return new ApiManager::ApiString(night);
}

API_CALL(Bunny::Api_setLanguage)
{
	QString lng = hRequest.GetArg("lng");
	SetLanguage(lng);
        return new ApiManager::ApiOk(Translator::tr("Bunny language is now %1", account).arg(lng));
}

API_CALL(Bunny::Api_getLanguage)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiString(GetLanguage());
}

API_CALL(Bunny::Api_setVersion)
{
        int i = hRequest.GetArg("version").toInt();
        SetGlobalSetting("Version",i);
        return new ApiManager::ApiOk(Translator::tr("Bunny version is now %1", account).arg(QString::number(i)));
}

API_CALL(Bunny::Api_getBootcode)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiString(GetGlobalSetting("Bootcode", "OJN01").toString());
}

API_CALL(Bunny::Api_getVersion)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiString(QString::number(GetGlobalSetting("Version", 2).toInt()));
}

API_CALL(Bunny::Api_setPublicVApi)
{
        bool p = (bool)(hRequest.GetArg("public").toInt());
        QString pub = p ? "public" : "private";
        SetGlobalSetting("VApiPublic",p);
        return new ApiManager::ApiOk(Translator::tr("Bunny is now %1 for VioletAPI", account).arg(Translator::tr(pub, account)));
}

API_CALL(Bunny::Api_getPublicVApi)
{
        Q_UNUSED(account);
        Q_UNUSED(hRequest);
        QString pub = GetGlobalSetting("VApiPublic",false).toBool() ? "public" : "private";
        return new ApiManager::ApiString(pub);
}

API_CALL(Bunny::Api_enableVApi)
{
	Q_UNUSED(hRequest);
	/* Get Token if exists */
	QString Token = GetGlobalSetting("VApiToken", "").toString();
	if(Token == "") {
		/* Generate random token */
		QByteArray Token = QCryptographicHash::hash(QUuid::createUuid().toString().toLatin1(), QCryptographicHash::Md5).toHex();
		SetGlobalSetting("VApiToken",Token);
	}
	SetGlobalSetting("VApiEnable",true);
	return new ApiManager::ApiOk(Translator::tr("VioletAPI enabled", account));
}

API_CALL(Bunny::Api_disableVApi)
{
	Q_UNUSED(hRequest);
	SetGlobalSetting("VApiEnable",false);
	return new ApiManager::ApiOk(Translator::tr("VioletAPI disabled", account));
}

API_CALL(Bunny::Api_getVApiStatus)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiString(GetGlobalSetting("VApiEnable", false).toBool() ? "enabled" : "disabled");
}

API_CALL(Bunny::Api_getVApiToken)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiString(GetGlobalSetting("VApiToken", "").toString());
}

API_CALL(Bunny::Api_setVApiToken)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	SetGlobalSetting("VApiToken",hRequest.GetArg("tk").toLatin1());
	return new ApiManager::ApiOk(Translator::tr("VioletAPI Token updated.", account));
}

API_CALL(Bunny::Api_getOneLast)
{
	Q_UNUSED(account);
	//if(!account.IsAdmin())
	//	return new ApiManager::ApiError("Access denied");

	QString hParam = hRequest.GetArg("param");
	if(hParam == "Last JabberDisconnection" || hParam == "Last JabberConnection" || hParam == "LastIP" || hParam == "LastRecord" || hParam == "LastLocate" || hParam == "LastLocateString" || hParam == "LastCron" || hParam == "Last PingConnection" || hParam == "Last Ping")
	{
		return new ApiManager::ApiString(GetGlobalSetting(hParam, QString("")).toString());
	}
	return new ApiManager::ApiError(Translator::tr("Bad value '%1' for argument '%2'", account).arg(hParam, "param"));
}

API_CALL(Bunny::Api_getAllLast)
{
	Q_UNUSED(hRequest);
	Q_UNUSED(account);
	//if(!account.IsAdmin())
	//	return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QStringList params;
	params << "Last JabberDisconnection" << "Last JabberConnection" << "Last PingConnection" << "LastIP" << "LastRecord" << "LastLocate" << "LastLocateString" << "LastCron" << "Last Ping";
	QMap<QString, QVariant> answer = QMap<QString, QVariant>();
	foreach(QString param, params)
	{
		answer.insert(param, GetGlobalSetting(param, QString("")));
	}

	return new ApiManager::ApiMappedList(answer);
}

API_CALL(Bunny::Api_getAllCronList)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString crons = "<crons>";
	QLinkedList<CronElement>::iterator i;
	QLinkedList<CronElement> list = Cron::ListAllBunnyCron(this);
	for (i = list.begin(); i != list.end(); ++i)
	{
		crons += "<cron>";
		crons += "<type>" + QString::number((*i).type) + "</type>";
		crons += "<plugin>" + (*i).plugin->GetName() + "</plugin>";
		crons += "<next_run>" + QString::number((*i).next_run) + "</next_run>";
		crons += "<callback>" + QString((*i).callback) + "</callback>";
		crons += "<interval>" + QString::number((*i).interval) + "</interval>";
		crons += "<day>" + QString::number((*i).day) + "</day>";
		crons += "<month>" + QString::number((*i).month) + "</month>";
		crons += "<data_int>" + QString::number((*i).data.toInt()) + "</data_int>";
		crons += "<data_string>" + (*i).data.toString() + "</data_string>";
		crons += "</cron>";
	}
	crons += "</crons>";
	return new ApiManager::ApiXml(crons);
}

API_CALL(Bunny::Api_getNextCronList)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> answer = QMap<QString, QVariant>();
	QMap<PluginInterface *, QDateTime>::iterator i;
	QMap<PluginInterface *, QDateTime> map = Cron::ListBunnyCron(this);
	for (i = map.begin(); i != map.end(); ++i)
		answer.insert(i.key()->GetName(), i.value().toTime_t());

	return new ApiManager::ApiMappedList(answer);
}

// V1
QStringList Bunny::AdpFileToLoad()
{
	QStringList list;
	//if(PluginManager::Instance().AdpFileToLoad(this))
	//	return list;

	// Call AdpFileToLoad for all 'bunny' plugins
	list.append(soundToSend);
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable() && p->GetName() == "clock")
		{
			list.append(p->AdpFileToLoad(this));
		}
	}
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable() && p->GetName() != "clock")
		{
			list.append(p->AdpFileToLoad(this));
		}
	}
	ClearSoundToSend();
	return list;
}

QString Bunny::SpecialBytecode()
{
	QString byteCode;
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable() && byteCode.length() == 0)
			byteCode = p->SpecialBytecode(this);
	}
	return byteCode;
}

void Bunny::UpdateServices()
{
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->SetServices(this);
	}
}

void Bunny::UpdateServicesImportant()
{
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->SetServicesImportant(this);
	}
}

QString Bunny::ChooseBytecode()
{
	QString byteCode;
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable() && byteCode.length() == 0)
			byteCode = p->ChooseBytecode(this);
	}
	if(byteCode.length() == 0)
	{
		byteCode = "default_violet";
	}
	return byteCode;
}

QByteArray Bunny::AddData()
{
	QByteArray data;
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			data += p->AddData(this);
	}
	return data;
}

void Bunny::AddInXmppTraffic(int t)
{
	if(trafficCount & 1)
		inXmppTraffic += t;
}

void Bunny::AddInHttpTraffic(int t)
{
	if(trafficCount & 2)
		inHttpTraffic += t;
}

void Bunny::AddOutXmppTraffic(int t)
{
	if(trafficCount & 1)
		outXmppTraffic += t;
}

void Bunny::AddOutHttpTraffic(int t)
{
	if(trafficCount & 2)
		outHttpTraffic += t;
}


void Bunny::ResetInXmppTraffic()
{
	inXmppTraffic = 0;
}

void Bunny::ResetInHttpTraffic()
{
	inHttpTraffic = 0;
}

void Bunny::ResetOutXmppTraffic()
{
	outXmppTraffic = 0;
}

void Bunny::ResetOutHttpTraffic()
{
	outHttpTraffic = 0;
}

void Bunny::SetService(int service, int value)
{
	if(value >= 0)
	{
		services.insert(service, value);
	}
	else
	{
		services.remove(service);
	}
}

int Bunny::GetService(int service)
{
	return services.value(service, 0);
}

QMap<int, int> Bunny::GetServices()
{
	return services;
}

bool Bunny::IsLimited() const
{
	Account * a = AccountManager::GetAccountByLogin(GetGlobalSetting("OwnerAccount").toByteArray());
	if(a)
	{
		if(a->IsPremium() || a->IsAdmin() || a->IsVip())
		{
			return false;
		}
	}
	return true;
}

