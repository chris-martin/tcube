#!/usr/bin/python
import gnomekeyring, sys
try:
  print gnomekeyring.find_items_sync(0, {'user': sys.argv[1]})[0].secret
except:
  pass

