#!/bin/python3

import os
import requests
from common import ConfigFile, key

if len(os.sys.argv) != 3:
    print(f"Usage: {os.sys.argv[0]} <show> <album>")
    exit(1)

show_name = os.sys.argv[1]
album_name = os.sys.argv[2]

config = ConfigFile(key.config_name)
frame_count = 0
while True:
    try:
        result: requests.Response = requests.get(f"{config[key.server]}/show/{show_name}/group/{album_name}/frame_count", headers={
            'X-Auth': config[key.app_token],
        }, timeout=999)
        if result.status_code == 200:
            frame_count = result.json()['count']
        elif result.status_code == 403:
            print(f"Error {result.status_code}: Invalid token")
        else:
            print(f"Error {result.status_code}")
        break
    except Exception as e:
        print(e)

if frame_count == -1:
    print(f"{show_name}-{album_name} is not found, check the spelling")
    exit(2)

config_name = f"{show_name}-{album_name}"
album_config = ConfigFile(config_name, ConfigFile.NEW)
if album_config.exists():
    print(f"{album_config.filename} exist, not overwriting")
    exit(3)

album_config[key.show] = show_name
album_config[key.album] = album_name
album_config[key.next_group] = None
album_config[key.frame_count] = frame_count
album_config[key.interval] = config[key.default_interval]
album_config[key.batch_size] = config[key.default_batch_size]
album_config[key.max_queue] = config[key.default_max_queue]
album_config[key.last_update] = 0
album_config[key.next_frame] = 1
album_config[key.posted] = 0
album_config[key.in_queue] = 0
album_config.save()
print(f"{album_config.filename} saved")

