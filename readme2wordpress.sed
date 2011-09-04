#!/bin/sed
# vim: set ts=4 et nu ai ff=unix backupcopy=yes syntax=sed indentexpr= :vim

# Replace Level1 Headlines
s/^#\s\(.\+\)\s#/=== \1 ===/

# Replace Level2 Headlines
s/^##\s\(.\+\)\s##/== \1 ==/

# Replace Level3 Headlines
s/^###\s\(.\+\)\s###/= \1 =/

# Replace escaped <
s/\\</</

# Remove Screenshot-Links
/^\[screenshot.\+/d
s/!\[.\+]\[screenshot.\+\]//

