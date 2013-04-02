
# Makefile for building oci_query.

CXX       = clang
CXXFLAGS  = -g -DOCI_IMPORT_LINKAGE -DOCI_CHARSET_ANSI \
  -I./includes -I$(ORACLE_HOME)/sdk/include
LDFLAGS   = -arch i386 -L/opt/oracle/instantclient/ -lclntsh -locilib -ljson

default: oci_query

install: oci_query
	cp oci_query /usr/local/bin/

install_test:
	cp tests/test_oci_query.php '/Volumes/Sensitive/Library/WebServer/Documents/babbage.cs.qc.cuny.edu/'

clean:
	rm -fr test_env oci_query *.o *.dSYM

