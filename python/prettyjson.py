import sys
import json

from util import *

data = json.load(sys.stdin)
print json_encode(data, pretty = True)
