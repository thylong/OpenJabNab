#ifndef _PLUGINWEATHER_H_
#define _PLUGINWEATHER_H_

#include <QUrl>
#include <QNetworkAccessManager>
#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"

#define PLUGIN_WEATHER_UNKNOW    0
#define PLUGIN_WEATHER_SUNNY     1
#define PLUGIN_WEATHER_RAIN      2
#define PLUGIN_WEATHER_SNOW      3
#define PLUGIN_WEATHER_STORM     4
#define PLUGIN_WEATHER_RAINSNOW  5
#define PLUGIN_WEATHER_FOG       6
#define PLUGIN_WEATHER_WIND      7
#define PLUGIN_WEATHER_CLOUD     8

#define PLUGIN_WEATHER_CONDITION_UNKNOW    "plugin_weather_condition_unknow"
#define PLUGIN_WEATHER_CONDITION_SUNNY     "plugin_weather_condition_sunny"
#define PLUGIN_WEATHER_CONDITION_RAIN      "plugin_weather_condition_rain"
#define PLUGIN_WEATHER_CONDITION_SNOW      "plugin_weather_condition_snow"
#define PLUGIN_WEATHER_CONDITION_STORM     "plugin_weather_condition_storm"
#define PLUGIN_WEATHER_CONDITION_RAINSNOW  "plugin_weather_condition_rainsnow"
#define PLUGIN_WEATHER_CONDITION_FOG       "plugin_weather_condition_fog"
#define PLUGIN_WEATHER_CONDITION_WIND      "plugin_weather_condition_wind"
#define PLUGIN_WEATHER_CONDITION_CLOUD     "plugin_weather_condition_cloud"

#define PLUGIN_WEATHER_FORECAST_UNKNOW    "plugin_weather_forecast_unknow"
#define PLUGIN_WEATHER_FORECAST_SUNNY     "plugin_weather_forecast_sunny"
#define PLUGIN_WEATHER_FORECAST_RAIN      "plugin_weather_forecast_rain"
#define PLUGIN_WEATHER_FORECAST_SNOW      "plugin_weather_forecast_snow"
#define PLUGIN_WEATHER_FORECAST_STORM     "plugin_weather_forecast_storm"
#define PLUGIN_WEATHER_FORECAST_RAINSNOW  "plugin_weather_forecast_rainsnow"
#define PLUGIN_WEATHER_FORECAST_FOG       "plugin_weather_forecast_fog"
#define PLUGIN_WEATHER_FORECAST_WIND      "plugin_weather_forecast_wind"
#define PLUGIN_WEATHER_FORECAST_CLOUD     "plugin_weather_forecast_cloud"

#define PLUGIN_WEATHER_TOMORROW_UNKNOW    "plugin_weather_tomorrow_unknow"
#define PLUGIN_WEATHER_TOMORROW_SUNNY     "plugin_weather_tomorrow_sunny"
#define PLUGIN_WEATHER_TOMORROW_RAIN      "plugin_weather_tomorrow_rain"
#define PLUGIN_WEATHER_TOMORROW_SNOW      "plugin_weather_tomorrow_snow"
#define PLUGIN_WEATHER_TOMORROW_STORM     "plugin_weather_tomorrow_storm"
#define PLUGIN_WEATHER_TOMORROW_RAINSNOW  "plugin_weather_tomorrow_rainsnow"
#define PLUGIN_WEATHER_TOMORROW_FOG       "plugin_weather_tomorrow_fog"
#define PLUGIN_WEATHER_TOMORROW_WIND      "plugin_weather_tomorrow_wind"
#define PLUGIN_WEATHER_TOMORROW_CLOUD     "plugin_weather_tomorrow_cloud"

class PluginWeather : public PluginInterface
{
	friend class PluginWeather_Worker;
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.services.weather" FILE "")

private slots:
	QString OnApiGet(Bunny *, QVariant);
	void analyseXml(QNetworkReply*);
	void analyseDone(bool, Bunny*, QByteArray);

public:
	PluginWeather();
	virtual ~PluginWeather();
	bool OnClick(Bunny *, PluginInterface::ClickType);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	bool OnRFID(Bunny * b, QByteArray const& tag);
	void OnCron(Bunny *, QVariant, unsigned int);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void AfterBunnyUnregistered(Bunny *) {};
	QString GetVersion() { return "2.2.2"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("2.2.1", "Improved name of city pronunciation");
		revisions.insert("2.2.2", "Use language defined for plugin");
		return revisions;
	}

	int GetWeatherFromCode(int);
	QString GetTranslatedWeather(int, QString, QString);
	int GetWindFromSpeed(int);
	QString GetTranslatedWind(int, QString);
	QString insertWindData(QString, int);
	QString insertWeatherData(QString, QString, QString, int, int);

	QStringList GetLanguages() { return QStringList() << "fr" << "en" << "es" << "it" << "de"; }
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("get", "");
                return list;
        }

	QHash<QString, QString> GetVoiceCommands(QString);

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_setDefaultCity);
	PLUGIN_BUNNY_API_CALL(Api_AddWebcast);
	PLUGIN_BUNNY_API_CALL(Api_RemoveWebcast);
	PLUGIN_BUNNY_API_CALL(Api_ListWebcast);
	PLUGIN_BUNNY_API_CALL(Api_addCity);
	PLUGIN_BUNNY_API_CALL(Api_removeCity);
	PLUGIN_BUNNY_API_CALL(Api_getCitiesList);
	PLUGIN_BUNNY_API_CALL(Api_getDefaultCity);
	PLUGIN_BUNNY_API_CALL(Api_AddRFID);
	PLUGIN_BUNNY_API_CALL(Api_RemoveRFID);
	PLUGIN_BUNNY_API_CALL(Api_ListRFID);
	PLUGIN_BUNNY_API_CALL(Api_getLang);
	PLUGIN_BUNNY_API_CALL(Api_setLang);
	PLUGIN_BUNNY_API_CALL(Api_getFrequency);
	PLUGIN_BUNNY_API_CALL(Api_setFrequency);

	PLUGIN_API_CALL(Api_setConditionGroup);
	PLUGIN_API_CALL(Api_getConditionGroup);
	PLUGIN_API_CALL(Api_setCondition);
	PLUGIN_API_CALL(Api_getCondition);
	PLUGIN_API_CALL(Api_getConditions);
	PLUGIN_API_CALL(Api_setTranslation);
	PLUGIN_API_CALL(Api_getTranslation);
	PLUGIN_API_CALL(Api_Translation);
 	PLUGIN_API_CALL(Api_GetAllCitiesList);

private:
	void getWeatherForCity(Bunny *, QString);
};

#endif
