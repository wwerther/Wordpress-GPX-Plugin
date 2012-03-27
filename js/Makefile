# Makefile for generating minified files
DIRS	+= helper
DIRS	+= flot

.PHONY: all

# we cheat and process all .js files instead of an exhaustive list
all: $(patsubst %.js,%.min.js,$(filter-out %.min.js,$(wildcard *.js))) make_subdir

%.min.js: %.js 
	yui-compressor $< -o $@

clean: clean_subdir
	$(RM) *.min.js

make_subdir:
	for dir in $(DIRS); do (cd $$dir; $(MAKE) $(1) || exit 1) || exit 1; done

clean_subdir:
	for dir in $(DIRS); do (cd $$dir; $(MAKE) clean || exit 1) || exit 1; done
