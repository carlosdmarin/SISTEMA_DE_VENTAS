#!/usr/bin/env python3
import sys
import json
import os

# ESCRIBIR LOG PARA VERIFICAR QUE PYTHON CORRE
with open('/tmp/python_debug.log', 'w') as f:
    f.write("Python script ejecutado\n")
    f.write(f"Argumentos: {sys.argv}\n")

try:
    from supabase import create_client
    with open('/tmp/python_debug.log', 'a') as f:
        f.write("Supabase importado correctamente\n")
except Exception as e:
    with open('/tmp/python_debug.log', 'a') as f:
        f.write(f"Error importando supabase: {str(e)}\n")
    print(json.dumps({'error': f'Error importando supabase: {str(e)}'}))
    sys.exit(1)

# Configuración
SUPABASE_URL = os.environ.get('SUPABASE_URL')
SUPABASE_KEY = os.environ.get('SUPABASE_KEY')

with open('/tmp/python_debug.log', 'a') as f:
    f.write(f"SUPABASE_URL: {SUPABASE_URL}\n")
    f.write(f"SUPABASE_KEY: {SUPABASE_KEY[:20] if SUPABASE_KEY else 'None'}...\n")

if not SUPABASE_URL or not SUPABASE_KEY:
    print(json.dumps({'error': 'Faltan variables SUPABASE_URL o SUPABASE_KEY'}))
    sys.exit(1)

supabase = create_client(SUPABASE_URL, SUPABASE_KEY)

def main():
    try:
        input_data = sys.stdin.read()
        if not input_data:
            print(json.dumps({'error': 'No se recibieron datos'}))
            return
            
        data = json.loads(input_data)
        action = data.get('action')
        
        if action == 'get_today':
            fecha = data.get('fecha')
            result = supabase.table('ventas').select('*').execute()
            print(json.dumps(result.data if result.data else []))
        elif action == 'create':
            venta = data.get('venta', {})
            result = supabase.table('ventas').insert(venta).execute()
            if result.data:
                print(json.dumps({'success': True, 'id_venta': result.data[0]['id_venta']}))
            else:
                print(json.dumps({'success': False, 'error': 'No se insertó'}))
        else:
            print(json.dumps({'error': f'Acción desconocida: {action}'}))
    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == '__main__':
    main()
