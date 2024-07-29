# -*- coding: utf-8 -*-
from BaseHTTPServer import BaseHTTPRequestHandler, HTTPServer
import base64
import json

# Credenciais codificadas em base64 (usuário: senha)
valid_credentials = "c2lnbWFjb21fdXJhOktxbzVkZUlsMFk="

class RequestHandler(BaseHTTPRequestHandler):
    def authenticate(self):
        auth_header = self.headers.get('Authorization')
        if auth_header:
            auth_type, credentials = auth_header.split()
            if auth_type.lower() == 'basic' and credentials == valid_credentials:
                return True
        self.send_response(401)
        self.send_header('WWW-Authenticate', 'Basic realm="Login Required"')
        self.end_headers()
        self.wfile.write('Autenticação falhou!')
        return False

    def do_POST(self):
        if not self.authenticate():
            return
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length)
        data = json.loads(post_data)
        print("Mensagem recebida via POST: {}".format(data))
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        response = {'status': 'success', 'message': 'Mensagem recebida com sucesso via POST!'}
        self.wfile.write(json.dumps(response))

    def do_GET(self):
        if not self.authenticate():
            return
        query = self.path.split('?')[-1]
        args = dict(qc.split('=') for qc in query.split('&')) if '?' in self.path else {}
        print("Requisição recebida via GET: {}".format(args))
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        response = {'status': 'success', 'message': 'Requisição recebida com sucesso via GET!'}
        self.wfile.write(json.dumps(response))

    def do_PUT(self):
        if not self.authenticate():
            return
        content_length = int(self.headers.get('Content-Length', 0))
        put_data = self.rfile.read(content_length)
        data = json.loads(put_data)
        print("Mensagem recebida via PUT: {}".format(data))
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        response = {'status': 'success', 'message': 'Mensagem recebida com sucesso via PUT!'}
        self.wfile.write(json.dumps(response))

    def do_DELETE(self):
        if not self.authenticate():
            return
        query = self.path.split('?')[-1]
        args = dict(qc.split('=') for qc in query.split('&')) if '?' in self.path else {}
        print("Requisição recebida via DELETE: {}".format(args))
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        response = {'status': 'success', 'message': 'Requisição recebida com sucesso via DELETE!'}
        self.wfile.write(json.dumps(response))

    def do_PATCH(self):
        if not self.authenticate():
            return
        content_length = int(self.headers.get('Content-Length', 0))
        patch_data = self.rfile.read(content_length)
        data = json.loads(patch_data)
        print("Mensagem recebida via PATCH: {}".format(data))
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        response = {'status': 'success', 'message': 'Mensagem recebida com sucesso via PATCH!'}
        self.wfile.write(json.dumps(response))

def run(server_class=HTTPServer, handler_class=RequestHandler, port=5000):
    server_address = ('', port)
    httpd = server_class(server_address, handler_class)
    print('Starting server on port {}'.format(port))
    httpd.serve_forever()

if __name__ == '__main__':
    run()