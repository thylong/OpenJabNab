inline PluginInterface * PluginMessageInterface::GetMessagePlugin()
{
	return PluginManager::Instance().GetPluginByName("messages");
}

inline bool PluginMessageInterface::SaveMessage(Bunny * b, QString file, int max)
{
	QStringList list;
	list.append(file);
	return SaveMessage(b, list, max);
}

inline bool PluginMessageInterface::SaveMessage(Bunny * b, QStringList files, int max)
{
	PluginInterface * plugin = GetMessagePlugin();
	if(plugin)
	{
		QString name = ((PluginInterface *)(this))->GetName();
		return QMetaObject::invokeMethod(plugin, "AddMessage", Q_ARG(Bunny*, b), Q_ARG(QString, name), Q_ARG(QStringList, files), Q_ARG(int, max));
	}
	return false;
}
inline bool PluginMessageInterface::SaveMessage(Bunny * b, QString file)
{
	QStringList list;
	list.append(file);
	return SaveMessage(b, list);
}

inline bool PluginMessageInterface::SaveMessage(Bunny * b, QStringList files)
{
	PluginInterface * plugin = GetMessagePlugin();
	if(plugin)
	{
		QString name = ((PluginInterface *)(this))->GetName();
		return QMetaObject::invokeMethod(plugin, "AddMessage", Q_ARG(Bunny*, b), Q_ARG(QString, name), Q_ARG(QStringList, files));
/*
		if(b->GetPluginSetting("messages", QString("Keep/%1").arg(name), b->GetPluginSetting("messages", "Keep/default", 0).toInt()).toInt())
		{
			QStringList list = b->GetPluginSetting("messages", "Messages", QStringList()).toStringList();
			LogDebug(name + " want to save a message");
			list.append(QString::number(QDateTime::currentDateTime().toTime_t()) + "|" + name + "|" + files.join("|"));
			b->SetPluginSetting("messages", "Messages", list);
			return true;
		}
		else
		{
			LogDebug(name + " want to save a message but it's not activated");
		}
*/
	}
	return false;
}

/*
void Bunny::AddMessage(QDateTime time, QString plugin , QStringList list)
{
	QString message = QString::number(time.toTime_t()) + "|" + plugin + "|" + list.join("|");
	messages.append(message);
}

int Bunny::CountMessages()
{
	return messages.count();
}

QString Bunny::TakeFirstMessage()
{
	if(messages.isEmpty())
		return "";
	return messages.takeFirst();
}

void Bunny::CleanMessages(QMap<QString, int> list)
{
	for (int i = messages.size() - 1; i>= 0; i--)
	{
		QStringList message = messages.at(i).split("|");
		QString plugin = message.at(1);
		if(list.contains(plugin))
		{
			if(QDateTime::fromTime_t(message.at(0).toInt()) <= QDateTime::currentDateTime().addSecs(-1 * 3600 * list.value(plugin)))
				messages.removeAt(i);
		}
		else
			messages.removeAt(i);
	}
}
 
void Bunny::CleanMessages()
{
	messages.clear();
}
 
void Bunny::CleanMessages(QString plugin)
{
	for (int i = messages.size() - 1; i>= 0; i--)
	{
		QStringList message = messages.at(i).split("|");
		if(message.at(1) == plugin)
			messages.removeAt(i);
	}
} 

void Bunny::CleanMessages(QDateTime date)
{
	for (int i = messages.size() - 1; i>= 0; i--)
	{
		QStringList message = messages.at(i).split("|");
		if(QDateTime::fromTime_t(message.at(0).toInt()) <= date)
			messages.removeAt(i);
	}
} 

void Bunny::CleanMessages(QDateTime date, QString plugin)
{
	for (int i = messages.size() - 1; i>= 0; i--)
	{
		QStringList message = messages.at(i).split("|");
		if(QDateTime::fromTime_t(message.at(0).toInt()) <= date && message.at(1) == plugin)
			messages.removeAt(i);
	}
} 
*/
