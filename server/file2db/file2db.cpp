#include <iostream>
#include <signal.h>
#include "convert.h"

File2Db * f;

void sigCatcher(int)
{
	f->Close();
	QMetaObject::invokeMethod(f, "quit", Qt::QueuedConnection);
}

int main( int argc, char **argv )
{
	signal(SIGINT, sigCatcher);
	signal(SIGTERM, sigCatcher);

	f = new File2Db(argc, argv);

	return 0;
}
