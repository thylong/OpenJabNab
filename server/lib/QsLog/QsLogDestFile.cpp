// Copyright (c) 2013, Razvan Petru
// All rights reserved.

// Redistribution and use in source and binary forms, with or without modification,
// are permitted provided that the following conditions are met:

// * Redistributions of source code must retain the above copyright notice, this
//   list of conditions and the following disclaimer.
// * Redistributions in binary form must reproduce the above copyright notice, this
//   list of conditions and the following disclaimer in the documentation and/or other
//   materials provided with the distribution.
// * The name of the contributors may not be used to endorse or promote products
//   derived from this software without specific prior written permission.

// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
// ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
// WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
// INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
// BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
// DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
// LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
// OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

#include "QsLogDestFile.h"
#include <QCoreApplication>
#include <QDir>
#include <QTextCodec>
#include <QDate>
#include <QtGlobal>
#include <iostream>


QsLogging::RotationStrategy::~RotationStrategy()
{
}

QsLogging::DayRotationStrategy::DayRotationStrategy()
    : mCurrentDate(QDate::currentDate())
{
}

void QsLogging::DayRotationStrategy::setInitialInfo(const QFile &file)
{
    mFileName = file.fileName();
    mPath = QFileInfo(file).path();
}

void QsLogging::DayRotationStrategy::includeMessageInCalculation(const QString &)
{
}

bool QsLogging::DayRotationStrategy::shouldRotate()
{
    return QDate::currentDate() != mCurrentDate;
}

// Algorithm assumes backups will be named filename.X, where 1 <= X <= mBackupsCount.
// All X's will be shifted up.
void QsLogging::DayRotationStrategy::rotate()
{
	QString fileName = QDir(mPath).absoluteFilePath(mFileName);

	QString archiveName = fileName;
	archiveName.replace(".log", "." + QDate::currentDate().addDays(-1).toString("yyyyMMdd") + ".log");
        QFile::rename(mFileName, archiveName);
        //const bool renamed = QFile::rename(mFileName, archiveName);
	mCurrentDate = QDate::currentDate();
}

QIODevice::OpenMode QsLogging::DayRotationStrategy::recommendedOpenModeFlag()
{
    return QIODevice::Append;
}

////////////////////////////////

QsLogging::FileDestination::FileDestination(const QString& filePath, RotationStrategyPtr rotationStrategy)
    : mRotationStrategy(rotationStrategy)
{
    mFile.setFileName(filePath);
    if (!mFile.open(QFile::WriteOnly | QFile::Text | QIODevice::Append))
        std::cerr << "QsLog: could not open log file " << qPrintable(filePath);
    mOutputStream.setDevice(&mFile);
    mOutputStream.setCodec(QTextCodec::codecForName("UTF-8"));

    mRotationStrategy->setInitialInfo(mFile);
}

void QsLogging::FileDestination::write(const QString& message)
{
    mRotationStrategy->includeMessageInCalculation(message);
    if (mRotationStrategy->shouldRotate()) {
        mOutputStream.setDevice(NULL);
        mFile.close();
        mRotationStrategy->rotate();
        if (!mFile.open(QFile::WriteOnly | QFile::Text | QIODevice::Append))
            std::cerr << "QsLog: could not reopen log file " << qPrintable(mFile.fileName());
        mRotationStrategy->setInitialInfo(mFile);
        mOutputStream.setDevice(&mFile);
    }

    mOutputStream << message << endl;
    mOutputStream.flush();
}

bool QsLogging::FileDestination::isValid()
{
    return mFile.isOpen();
}
