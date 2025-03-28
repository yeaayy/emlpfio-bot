#!/bin/python3

import os
from common import ConfigFile, key, require_json

if len(os.sys.argv) != 2:
    print(f"Usage: {os.sys.argv[0]} <name>")
    exit(1)

name = os.sys.argv[1]
require_json(name)

config = ConfigFile(key.config_name)
config[key.active].append(name)
config.flags |= ConfigFile.MODIFIED
config.save()
