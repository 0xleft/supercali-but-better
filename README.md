# Dockerized supercali calendar cuz schools still use this like wtF?

## Production?

If you want to run this in production there are a few points to consider:

1. You might be crazy
2. This stuff is as broken as it gets
3. change config.php
4. all admin querys are not sanitized so wtf

## Problems

XSS everywhere, old version have sqli and access control problems its actualy pretty fun to find problems in this app :)

## Licensing

supercali is licensed under GPL which can be found here:
http://supercali.inforest.com/license.php

# Fixes cuz i cannot be asked again:

modules.php + 60-61