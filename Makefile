# Main Makefile #

include config.mk

####################
#       Client     #
####################

client:
	cd Client ; make all

initDB:
	${JAVA} -classpath ./Client:./database edu.rice.rubis.client.InitDB ${PARAM}

emulator:
	${JAVA} -classpath ./Client edu.rice.rubis.client.ClientEmulator


############################
#       Global rules       #
############################

DIRS = Client

all: flush_cache
	-for d in ${DIRS}; do (cd $$d ; ${MAKE} all); done

world: all javadoc

javadoc :
	-for d in ${DIRS}; do (cd $$d ; ${MAKE} javadoc); done

clean:
	-for d in ${DIRS}; do (cd $$d ; ${MAKE} clean); done

flush_cache: bench/flush_cache.c
	gcc bench/flush_cache.c -o bench/flush_cache
