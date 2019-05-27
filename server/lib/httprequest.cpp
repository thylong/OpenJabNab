#include <QEventLoop>
#include <QNetworkAccessManager>
#include <QUrl>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QTime>
#include <QObject>
#include <QStringList>
#include <QUrl>
#include <QUrlQuery>
#include "httprequest.h"
#include "log.h"

HTTPRequest::HTTPRequest(QByteArray const& data):type(INVALID)
{
	if (data.size() < 4 || *(int*)data.left(4).constData() != data.size())
	{
		LogError("HTTP Request : Invalid data");
		return;
	}
	RequestType t = (RequestType)data.at(4);
	QByteArray content = data.mid(5);
	switch (t)
	{
		case GET:
			rawHeaders = content.left(content.indexOf('\0')); // Copy headers, stop at first \x00
			rawUri = content.mid(rawHeaders.length()+1); // Copy URI
			type = GET;
			break;

		case POST:
		{
			rawHeaders = content.left(content.indexOf('\0')); // Copy headers, stop at first \x00
			content = content.mid(rawHeaders.length()+1);
			rawUri = content.left(content.indexOf('\0')); // Copy URI
			rawPostData = content.mid(rawUri.length()+1);
			// Parse Post Data
			QList<QByteArray> listOfParams = rawPostData.split('&');
			foreach(QByteArray param, listOfParams)
			{
				QByteArray key = param.left(param.indexOf('='));
				QByteArray value = param.mid(param.indexOf('=')+1);
				formPostData[QUrl::fromPercentEncoding(key)] = QUrl::fromPercentEncoding(value);
			}
			type = POST;
			break;
		}

		case POSTRAW:
			rawHeaders = content.left(content.indexOf('\0')); // Copy headers, stop at first \x00
			content = content.mid(rawHeaders.length()+1);
			rawUri = content.left(content.indexOf('\0')); // Copy URI
			rawPostData = content.mid(rawUri.length()+1);
			type = POSTRAW;
			break;

		default:
			LogError("HTTP Request : Invalid type");
			return;
	}
	// Parse URI
    QUrl url(rawUri);
	QUrlQuery urlq(url);
	uri = url.path();
	if(url.hasQuery())
	{
		QList<QPair<QString, QString> > items = urlq.queryItems();
		typedef QPair<QString, QString> queryItemDef;
		foreach(queryItemDef item, items)
			getData[QUrl::fromPercentEncoding(item.first.toLatin1())] = QUrl::fromPercentEncoding(item.second.toLatin1());
	}
}

bool HTTPRequest::IsValid()
{
	if(getData.contains("time"))
	{
		if(getData.contains("action"))
		{
			if(getData.value("action") == "del")
			{
				return true;
			}
		}
		QString t = getData.value("time");
		t = t.replace("h", ":", Qt::CaseInsensitive);
		if(QString::number(t.toInt()) == t)
			t += ":00";

		QTime time = QTime::fromString(t, "hh:mm");
		if(time.isValid())
		{
			getData["time"] = time.toString("hh:mm");
			return true;
		}
		return false;
	}
	return true;
}

QString HTTPRequest::GetIP() const
{
	QStringList lst;
	QString str = rawHeaders;
	lst = str.split(QLatin1String("\r\n"));
	lst.removeAll(QString()); // No empties
	if (!lst.isEmpty())

	for(QStringList::Iterator it = lst.begin(); it != lst.end(); ++it)
	{
		int i = it->indexOf(QLatin1Char(':'));
		if (i != -1)
			if(it->left(i).trimmed() == "X-FORWARDED-FOR")
				return it->mid(i + 1).trimmed();
	}
	return "";
}

QByteArray HTTPRequest::ForwardTo(QString const& server)
{
	QByteArray answer;
	QEventLoop loop;
    QNetworkAccessManager http;
    QNetworkRequest req(QUrl(server+rawUri));

	{
		QStringList lst;
		QString str = rawHeaders;
		lst = str.split(QLatin1String("\r\n"));
		lst.removeAll(QString()); // No empties
		if (!lst.isEmpty())

		for(QStringList::Iterator it = lst.begin(); it != lst.end(); ++it)
		{
			int i = it->indexOf(QLatin1Char(':'));
			if (i != -1)
            {
                const auto& key(it->left(i).trimmed().toLatin1());
                const auto& val(it->mid(i + 1).trimmed().toLatin1());
				req.setRawHeader(key, val);
            }
		}
	}
	req.setRawHeader("Connection","");
    req.setRawHeader("Host", server.toLatin1());
    QNetworkReply* rep;
	if (type == GET)
	{
        rep = http.get(req);
	}
	else
	{
        rep = http.post(req,rawPostData);
	}
    QObject::connect(rep, SIGNAL(finished()), &loop, SLOT(quit()));
    QObject::connect(rep, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
    loop.exec();

	if(rep->error() != QNetworkReply::NoError)
	{
		LogError(QString("Network error %1").arg(rep->error()));
		return QByteArray();
	}
	answer = rep->readAll();
    delete rep;
	return answer;
}

QString HTTPRequest::toString() const
{
	QString s;
	s.append(QString("<ul><li>URL : %1</li>").arg(QString(uri)));
	s.append("<li>Get Args : <br /><ul>");
	foreach (QString str, getData.keys())
		s.append(QString("<li>%1 => %2</li>").arg(str,getData.value(str)));
	s.append("</ul></li>");
	if(type == POST)
	{
		s.append("<li>Post Args : <br /><ul>");
		foreach (QString str, formPostData.keys())
			s.append(QString("<li>%1 => %2</li>").arg(str,formPostData.value(str)));
		s.append("</ul></li>");
	}
	s.append("</ul>");
	return s;
}
