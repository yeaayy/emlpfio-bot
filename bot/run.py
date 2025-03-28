#!/bin/python3

import datetime as dt
import time
import requests
import socket

from common import ConfigFile, key, cfg

time_format = "%Y-%m-%d %H:%M:%S"
requests.get
def create_post(s: requests.Session, super_config: ConfigFile, config: ConfigFile):
    try:
        result: requests.Response = s.post(f"{super_config[key.server]}/show/{config[key.show]}/group/{config[key.album]}/frame/{config[key.next_frame]}/post",
            headers = {
                'X-Auth': super_config[key.app_token],
            },
            timeout = 300
        )
        if result.status_code == 200:
            result = result.json()
            print(f"{config[key.show]}.{config[key.album]} frame {config[key.next_frame]} posted at {dt.datetime.now().strftime(time_format)}")
            config[key.next_frame] += 1
            return True
        else:
            print(f"Error {result.status_code} {result.text}")
    except Exception as e:
        print(f"Error {e}")
        time.sleep(15)
    return False

def get_frame_count(s: requests.Session, config: ConfigFile, super_config: ConfigFile):
    while True:
        try:
            result: requests.Response = s.get(f"{super_config[key.server]}/show/{config[key.show]}/group/{config[key.album]}/frame_count", headers = {
                'X-Auth': super_config[key.app_token],
            }, timeout=999)
            if result.status_code == 200:
                return result.json()['count']
            elif result.status_code == 403:
                print(f"Error {result.status_code}: Invalid token")
            else:
                print(f"Error {result.status_code}: {result.raw}")
        except Exception as e:
            print(e)

def run(config: ConfigFile, super_config: ConfigFile, current_time: float):
    interval = config[key.interval]
    ref_time = super_config[key.ref_time]
    last_batch_index = int(config[key.last_update] - ref_time) // interval
    curr_batch_index = int(current_time - ref_time) // interval
    if last_batch_index == curr_batch_index and config[key.posted] >= config[key.batch_size]:
        return False
    config[key.last_update] = current_time
    config[key.posted] = 0
    config.save()

    s = requests.Session()
    if config[key.frame_count] <= 0:
        config[key.frame_count] = get_frame_count(s, config, super_config)
    while config[key.posted] < config[key.batch_size] and config[key.next_frame] <= config[key.frame_count]:
        if create_post(s, super_config, config):
            config[key.posted] += 1
            config.save()
    print('(break)')
    return config[key.next_frame] > config[key.frame_count]

config = ConfigFile(key.config_name)

# s.mount('https://', host_header_ssl.HostHeaderSSLAdapter())
# server_ip = socket.gethostbyname(config[key.server])

while len(config[key.active]) > 0:
    current_time = dt.datetime.now().timestamp()
    active_size = len(config[key.active])
    i = 0
    while i < active_size:
        name = config[key.active][i]
        active_config = ConfigFile(name)
        if run(active_config, config, current_time):
            config[key.active].remove(name)
            config.flags |= ConfigFile.MODIFIED
            i -= 1
            active_size -= 1

            next_group = active_config[key.next_group]
            if next_group != None:
                config[key.active].append(next_group)
                next_config = ConfigFile(next_group)
                next_config[key.last_update] = active_config[key.last_update]
                next_config.save()
                active_size += 1
        i += 1
        config.save()
        active_config.save()
    time.sleep(cfg.frac_time)
