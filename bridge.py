#!/usr/bin/env python3
import sys
import json
import os
from supabase import create_client, Client

# Configuración de Supabase
url = "https://ownjmawswuygfhltlzts.supabase.co"
key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im93bmptYXdzdXd5Z2ZsaHRsenRzIiwicm9sZSI6ImFub24iLCJpYXQjoxNzEzOTY1ODYwLCJleHAiOjIwMjk1NDE4NjB9.VEkbC4CmJ8P2Cp6dxwVfILXZvRL9YJrmZ7VhqR2pGZg" # <-- ¡USA TU API KEY REAL AQUÍ!
supabase: Client = create_client(url, key)

# Leer la acción y los datos desde PHP
try:
    data = json.load(sys.stdin)
    action = data['action']
    
    if action == 'get_today':
        fecha = data.get('fecha')
        response = supabase.table('ventas').select('*').gte('fecha_registro', fecha).order('fecha_registro', desc=True).execute()
        print(json.dumps(response.data))
    elif action == 'create':
        venta = data.get('venta')
        response = supabase.table('ventas').insert(venta).execute()
        print(json.dumps(response.data[0] if response.data else {'success': False}))
    elif action == 'pay':
        id_venta = data.get('id')
        response = supabase.table('ventas').update({'estado': 'cancelado'}).eq('id_venta', id_venta).execute()
        print(json.dumps({'success': True}))
    else:
        print(json.dumps({'error': 'Acción no válida'}))
except Exception as e:
    print(json.dumps({'error': str(e)}))
