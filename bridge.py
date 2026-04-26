#!/usr/bin/env python3
import sys
import json
import os
from supabase import create_client, Client
from datetime import datetime
import pytz

# Configurar zona horaria de Perú
LIMA_TZ = pytz.timezone('America/Lima')

# Configuración de Supabase
SUPABASE_URL = os.environ.get('SUPABASE_URL')
SUPABASE_KEY = os.environ.get('SUPABASE_KEY')

if not SUPABASE_URL or not SUPABASE_KEY:
    print(json.dumps({'error': 'Faltan variables SUPABASE_URL o SUPABASE_KEY'}))
    sys.exit(1)

try:
    supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)
except Exception as e:
    print(json.dumps({'error': f'Error conectando a Supabase: {str(e)}'}))
    sys.exit(1)

def get_today(fecha):
    try:
        result = supabase.table('ventas')\
            .select('*')\
            .gte('fecha_registro', f'{fecha} 00:00:00')\
            .lte('fecha_registro', f'{fecha} 23:59:59')\
            .order('fecha_registro', desc=True)\
            .execute()
        return result.data if result.data else []
    except Exception as e:
        return {'error': str(e)}

def get_by_date_range(desde, hasta):
    try:
        result = supabase.table('ventas')\
            .select('*')\
            .gte('fecha_registro', f'{desde} 00:00:00')\
            .lte('fecha_registro', f'{hasta} 23:59:59')\
            .order('fecha_registro', desc=True)\
            .execute()
        return result.data if result.data else []
    except Exception as e:
        return {'error': str(e)}

def create_venta(venta):
    try:
        result = supabase.table('ventas').insert(venta).execute()
        if result.data and len(result.data) > 0:
            return {'success': True, 'id_venta': result.data[0]['id_venta']}
        return {'success': False, 'error': 'No se pudo insertar'}
    except Exception as e:
        return {'success': False, 'error': str(e)}

def pagar_venta(id_venta):
    try:
        result = supabase.table('ventas')\
            .update({'estado': 'cancelado'})\
            .eq('id_venta', id_venta)\
            .execute()
        if result.data and len(result.data) > 0:
            return {'success': True}
        return {'success': False, 'error': 'Venta no encontrada'}
    except Exception as e:
        return {'success': False, 'error': str(e)}

def main():
    try:
        input_data = sys.stdin.read()
        if not input_data:
            print(json.dumps({'error': 'No se recibieron datos'}))
            return
            
        data = json.loads(input_data)
        action = data.get('action')
        
        if action == 'get_today':
            fecha = data.get('fecha', datetime.now(LIMA_TZ).strftime('%Y-%m-%d'))
            result = get_today(fecha)
        elif action == 'get_by_date_range':
            desde = data.get('desde')
            hasta = data.get('hasta')
            if not desde or not hasta:
                result = {'error': 'Faltan fechas'}
            else:
                result = get_by_date_range(desde, hasta)
        elif action == 'create':
            venta = data.get('venta', {})
            result = create_venta(venta)
        elif action == 'pay':
            id_venta = data.get('id')
            if not id_venta:
                result = {'error': 'Falta ID'}
            else:
                result = pagar_venta(id_venta)
        else:
            result = {'error': f'Acción desconocida: {action}'}
        
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == '__main__':
    main()
