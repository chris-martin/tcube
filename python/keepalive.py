import sys
from urllib2 import Request, urlopen

from util import *

parser = arg_parser()
args = parser.parse_args()

output = { 'status': 500 }

def exit():
  print json_encode(output, args.pretty)
  sys.exit()

if args.verbose: output['log'] = []
def verbose(x):
  if args.verbose: output['log'].append(x)

try:
  request = Request('https://t-square.gatech.edu/')
  add_cookieheader_from_json(request, args.cookies)
  response = urlopen(request).read()
  verbose(response)
  output['status'] = 200

except Exception as e:
  verbose(e.message)

finally:
  exit()
