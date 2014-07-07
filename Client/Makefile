include ../config.mk

####################
#       Client     #
####################

ClientFiles = URLGenerator URLGeneratorPHP RUBiSProperties Stats \
	      TransitionTable ClientEmulator UserSession InitDB

BeansFiles = TimeManagement

all_client_sources =  $(addprefix src/edu/rice/rubis/client/, $(addsuffix .java, $(ClientFiles))) $(addprefix src/edu/rice/rubis/beans/, $(addsuffix .java, $(BeansFiles)))
all_client_obj = $(addprefix src/edu/rice/rubis/client/, $(addsuffix .class, $(ClientFiles))) $(addprefix src/edu/rice/rubis/beans/, $(addsuffix .class, $(BeansFiles)))

client: $(all_client_obj)
	${JAR} cvf rubis_client.jar -C src dummy.txt $(all_client_obj)
	zip -d rubis_client.jar dummy.txt

all: client

initDB:
	${JAVA} -classpath .:./database edu.rice.rubis.client.InitDB ${PARAM}

emulator:
	${JAVA} -classpath . edu.rice.rubis.client.ClientEmulator

javadoc :
	${MKDIR} -p doc
	${JAVADOC} -d ./doc -bootclasspath ${CLASSPATH} -version -author -windowtitle "RUBiS Client API" -header "<b>RUBiS (C)2001 Rice University/INRIA</b><br>"  edu.rice.rubis.client

clean:
	${RM} -Rf edu/rice/rubis/client/*.class edu/rice/rubis/beans/*.class doc/* rubis_client.jar

