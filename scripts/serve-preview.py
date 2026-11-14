#!/usr/bin/env python3
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from pathlib import Path
class Handler(SimpleHTTPRequestHandler):
 def __init__(self,*args,**kwargs):super().__init__(*args,directory=str(Path(__file__).resolve().parent.parent/'build'),**kwargs)
 def do_GET(self):
  if self.path.startswith('/docs/'):
   self.path=self.path[len('/docs'):];return super().do_GET()
  self.send_error(404)
ThreadingHTTPServer(('127.0.0.1',8899),Handler).serve_forever()
