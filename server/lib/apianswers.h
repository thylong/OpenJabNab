#pragma once

struct OJN_EXPORT ApiAnswers
{
  class OJN_EXPORT Answer
	{
		public:
			virtual ~Answer() {}
			virtual QByteArray GetData(); // UTF8
			virtual QString GetInternalData() = 0;

		protected:
			QString SanitizeXML(QString const&);
	};

// Internal classes
	class OJN_EXPORT Clear : public Answer
	{
		public:
			QByteArray GetData(); // UTF8
			Clear():string(QString()) {}
			Clear(QString s):string(s) {}
			QString GetInternalData() { return string; }
		private:
			QString string;
	};

	class OJN_EXPORT Error : public Answer
	{
		public:
			Error(QString s):error(s) {}
			QString GetInternalData();
		private:
			QString error;
	};

	class OJN_EXPORT Xml : public Answer
	{
		public:
			Xml():string(QString()) {}
			Xml(QString s):string(s) {}
			QString GetInternalData() { return string; }
		private:
			QString string;
	};

	class OJN_EXPORT Ok : public Answer
	{
		public:
			Ok():string(QString()) {}
			Ok(QString s):string(s) {}
			QString GetInternalData();
		private:
			QString string;
	};

	class OJN_EXPORT String : public Answer
	{
		public:
			String(QString s):string(s) {}
			QString GetInternalData();
		private:
			QString string;
	};

	class OJN_EXPORT List : public Answer
	{
		public:
			List(QList<QString> l):list(l) {}
			QString GetInternalData();
		private:
			QList<QString> list;
	};

	class OJN_EXPORT MappedList : public Answer
	{
		public:
			MappedList(QMap<QString, QVariant> l):list(l) {}
			QString GetInternalData();
		private:
			QMap<QString, QVariant> list;
	};

	class OJN_EXPORT Violet : public Answer
	{
		public:
			Violet(QString m, QString c) { AddMessage(m, c); }
			Violet():string(QString()) {}
			Violet(QString s):string(s) {}
			QByteArray GetData();
			void AddMessage(QString, QString);
			void AddEarPosition(int, int);
			void AddXml(QString s) { string += s; }
			QString GetInternalData() { return string; }
		private:
			QString string;
	};
};
