#!/bin/bash -x

cat \
  base.css \
  form.css \
  page.css \
  info.css \
  note.css \
  menu.css > radix-full.css


perl -MCSS::Minifier -e 'print CSS::Minifier::minify(input => *STDIN)' < radix-full.css > radix.css
sed -i -e :a -e '$!N; s/,\n/,/; ta' radix.css

# @see http://davidgolightly.blogspot.com/2007/07/one-line-css-minifier.html
# $ cat sourcefile.css \
#   | sed -e 's/^[ \t]*//g; s/[ \t]*$//g; s/\([:{;,]\) /\1/g; s/ {/{/g; s/\/\*.*\*\///g; /^$/d' \
#   | sed -e :a -e '$!N; s/\n\(.\)/\1/; ta' >target.css