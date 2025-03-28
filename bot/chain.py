#!/bin/python3

import os
from common import ConfigFile, key, require_json

if len(os.sys.argv) != 3:
    print(f"Usage: {os.sys.argv[0]} <from> <next>")
    exit(1)

prev = require_json(os.sys.argv[1])
next = require_json(os.sys.argv[2])

config = ConfigFile(prev)
config[key.next_group] = next
config.save()
