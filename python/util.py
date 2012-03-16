import json
from argparse import ArgumentParser
from cookielib import Cookie, CookieJar
from datetime import datetime
import re

def json_encode(x, pretty):
  if pretty: return json.dumps(x, sort_keys = True, indent = 2)
  return json.dumps(x, separators = (',', ':'))

def json_decode(x):
  return json.loads(x)

def arg_parser(cookies=True):
  parser = ArgumentParser()
  if cookies: parser.add_argument('--cookies', required=True, help='cookie list in a json array')
  parser.add_argument('--verbose', action='store_true', help='print extra information')
  parser.add_argument('--pretty', action='store_true', help='produce human-readable output')
  return parser

def json_to_cookieheader(json_str):
  cookies = json_to_cookiejar(json_str)
  return '; '.join(map(lambda x: '{0}={1}'.format(x.name, x.value), cookies))

def add_cookieheader_from_json(request, json_str):
  request.add_header('Cookie', json_to_cookieheader(json_str))

def cookiejar_to_list(jar):
  return map(lambda x: {
    'domain': x.domain,
    'name': x.name,
    'value': x.value,
    'path': x.path,
    'path_specified': x.path_specified,
    'expires': x.expires
  }, jar)

def json_to_cookielist(json_str):
  return list_to_cookielist(json_decode(json_str))

def list_to_cookielist(list):
  return map(dict_to_cookie, list)

def list_to_cookiejar(list):
  jar = CookieJar()
  for cookie in list_to_cookielist(list):
    jar.set_cookie(cookie)
  return jar

def json_to_cookiejar(json_str):
  return list_to_cookiejar(json.loads(json_str))

def dict_to_cookie(dict):
  c = Cookie(
    domain = dict['domain'],
    name = dict['name'],
    value = dict['value'],
    path = dict['path'],
    path_specified = dict['path_specified'],
    expires = dict['expires'],
    version = 0,
    port = None,
    port_specified = False,
    domain_specified = False,
    domain_initial_dot = False,
    secure = False,
    discard = False,
    comment = None,
    comment_url = None,
    rest = {}
  )
  return c

_months = { 'jan': '01', 'feb': '02', 'mar': '03', 'apr': '04',
            'may': '05', 'jun': '06', 'jul': '07', 'aug': '08',
            'sep': '09', 'oct': '10', 'nov': '11', 'dec': '12' }

def reformat_tsquare_datetime(x):
  month = _months[x[0:3].lower()]
  x = re.sub('^[a-z]+ ', '{0} '.format(month), x, flags = re.I)
  x = datetime.strptime(x, '%m %d, %Y %I:%M %p')
  return format_datetime(x)

def format_datetime(x):
  return x.strftime('%Y-%m-%d %H:%M')

