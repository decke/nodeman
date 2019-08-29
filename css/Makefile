all: nodeman.min.css

nodeman.min.css:
	sassc -t compressed nodeman.sass $@

clean:
	rm -f nodeman.min.css

.PHONY: all clean
