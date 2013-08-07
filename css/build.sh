#!/bin/bash -x

cat \
  base.less \
  html.less \
  grid.less \
  list.css \
  form.css \
  page.css \
  info.css \
  note.css \
  menu.css \
  debug.css \
  > radix.less

# perl -MCSS::Minifier -e 'print CSS::Minifier::minify(input => *STDIN)' < radix-full.css > radix.css
# sed -e :a -e '$!N; s/,\n/,/; ta' < radix-full.css > radix.css

# cat debug.css radix-full.css > radix-debug.css

# @see http://davidgolightly.blogspot.com/2007/07/one-line-css-minifier.html
# $ cat sourcefile.css \
#   | sed -e 's/^[ \t]*//g; s/[ \t]*$//g; s/\([:{;,]\) /\1/g; s/ {/{/g; s/\/\*.*\*\///g; /^$/d' \
#   | sed -e :a -e '$!N; s/\n\(.\)/\1/; ta' >target.css

lessc \
	--compress \
	--strict-units=on \
	radix.less radix.css
