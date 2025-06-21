
import os
import json
import time

class key:
    config_name = "config.json"
    server = "server"
    active = "active"
    app_token = "app_token"
    ref_time = "ref_time"
    default_interval = "default_interval"
    default_batch_size = "default_batch_size"
    default_max_queue = "default_max_queue"
    next_group = "next_group"

    show = "show"               # (const)
    album = "album"             # (const)
    frame_count = "frame_count" # (const)
    interval = "interval"       # (const)
    batch_size = "batch_size"   # (const) normal number of frame per interval
    max_queue = "max_queue"     # (const) maximum number of frame per interval
    last_update = "last_update" # 
    next_frame = "next_frame"   # 
    posted = "posted"           # number of frame posted in this interval
    in_queue = "in_queue"       # number of frame left to be posted

class cfg:
    frac_time = 30

class ConfigFile:
    NEW = 1 << 0
    READONLY = 1 << 1
    MODIFIED = 1 << 2

    def __init__(self, filename: str, flags: int = 0):
        self.flags = flags
        if not filename.endswith(".json"):
            filename += '.json'
        self.filename = os.path.join(os.path.dirname(__file__), filename)
        if (flags & ConfigFile.NEW) == 0:
            with open(self.filename) as file:
                self.data = json.load(file)
        else:
            self.data = {}

    def __getitem__(self, key: str):
        return self.data[key]

    def __setitem__(self, key: str, value):
        if (self.flags & ConfigFile.READONLY) == 0:
            self.data[key] = value
            self.flags |= ConfigFile.MODIFIED
        else:
            raise TypeError('This config is readonly')

    def exists(self):
        return os.path.exists(self.filename)

    def save(self):
        if (self.flags & ConfigFile.MODIFIED) == 0:
            return
        tmp_file = f"{self.filename}.tmp"
        while True:
            try:
                with open(tmp_file, 'w') as file:
                    json.dump(self.data, file, indent=4)
                    file.flush()
                    os.fsync(file.fileno())
                os.replace(tmp_file, self.filename)
                break
            except OSError as e:
                time.sleep(5)
        self.flags &= ~ConfigFile.MODIFIED


def require_json(name: str):
    if not name.endswith(".json"):
        name += ".json"
    fullname = os.path.join(os.path.dirname(__file__), name)
    if not os.path.isfile(fullname):
        print(f"{fullname} is not a file.")
        exit(1)
    return name
