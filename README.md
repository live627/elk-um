# Ultimate Menu
[![Open Issues](http://img.shields.io/github/issues/live627/elk-um.svg?style=flat)](https://github.com/live627/elk-um/issues)
[![Current Release](https://img.shields.io/github/release/live627/elk-umsvg?style=flat)](https://github.com/live627/elk-um/releases)
[![License](http://img.shields.io/badge/License-ISC-green.svg?style=flat)](http://opensource.org/licenses/ISC)
## Introduction:
This is a direct port of my SMF mod to Elkarte.

This is a tool for configuring the main menu within Elkarte, allowing custom buttons to be added at will, complete with children and grandchildren menus (so, a main menu item, a dropdown and a follow-on dropdown)

## Known issues:
-  The select lists are lmimited to only 2em (quite bizarre,  but whatever)
-  Grandchild butons cannot be added using 'before'  or 'after'; not sure how to fix this

###Missing for first release:
-  Fixing select list height

###Future plans:
-  Complete rewrite using objects
-  Follow PSR-2 and PSR-4
-  Use generators (PHP 5.5+)
-  Use closures (PHP 5.3+)
-  Use late statiic bindigs (PHP 5.3+)
-  any suggestion that would come up in the topic

### License:
{% include license.MD param="MIT" %}
