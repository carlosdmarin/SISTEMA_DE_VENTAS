#!/usr/bin/env python3
import sys
import json
import os
from supabase import create_client, Client
from datetime import datetime
import pytz

# Configurar zona horaria de Perú
LIMA_TZ = pytz.timezone('America/Lima')

# Configuración de Supabase - Usar variables de entorno de Render
SUPABASE_URL = os.environ.get('SUPABASE_URL')
SUPABASE_KEY = os.environ.get('SUPABASE_KEY')

# Validar que las variables existen
if not SUPABASE_URL or not SUPABASE_KEY:
    print(json.dumps({'error': 'Faltan variables de entorno SUPABASE_URL o SUPABASE_KEY'}), file=sys.stderr)
    sys.exit(1)

try:
    supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)
except Exception as e:
    print(json.dumps({'error': f'Error conectando a Supabase: {str(e)}'}), file=sys.stderr)
    sys.exit(1)

def get_today(fecha):
    """Obtener ventas de una fecha específica"""
    try:
        result = supabase.table('ventas')\
            .select('*')\
            .gte('fecha_registro', f'{fecha} 00:00:00')\
            .lte('fecha_registro', f'{fecha} 23:59:59')\
            .execute()
        return result.data
    except Exception as e:
        print(f"Error en get_today: {e}", file=sys.stderr)
        return {'error': str(e)}

def get_by_date_range(desde, hasta):
    """Obtener ventas en un rango de fechas"""
    try:
        result = supabase.table('ventas')\
            .select('*')\
            .gte('fecha_registro', f'{desde} 00:00:00')\
            .lte('fecha_registro', f'{hasta} 23:59:59')\
            .order('fecha_registro', desc=True)\
            .execute()
        return result.data
    except Exception as e:
        print(f"Error en get_by_date_range: {e}", file=sys.stderr)
        return []

def create_venta(venta):
    """Crear una nueva venta con hora local"""
    try:
        if 'fecha_registro' not in venta or not venta['fecha_registro']:
            venta['fecha_registro'] = datetime.now(LIMA_TZ).strftime('%Y-%m-%d %H:%M:%S')
        
        print(f"Insertando venta: {venta}", file=sys.stderr)
        result = supabase.table('ventas').insert(venta).execute()
        
        if result.data and len(result.data) > 0:
            return {'id_venta': result.data[0]['id_venta'], 'success': True}
        return {'error': 'No se pudo insertar, respuesta vacía'}
    except Exception as e:
        print(f"Error en create_venta: {e}", file=sys.stderr)
        return {'error': str(e)}

def pagar_venta(id_venta):
    """Marcar una venta como pagada"""
    try:
        result = supabase.table('ventas')\
            .update({'estado': 'cancelado'})\
            .eq('id_venta', id_venta)\
            .execute()
        if result.data and len(result.data) > 0:
            return {'success': True}
        return {'success': False, 'error': 'Venta no encontrada'}
    except Exception as e:
        print(f"Error en pagar_venta: {e}", file=sys.stderr)
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
                result = {'error': 'Faltan fechas desde/hasta'}
            else:
                result = get_by_date_range(desde, hasta)
        elif action == 'create':
            venta = data.get('venta', {})
            result = create_venta(venta)
        elif action == 'pay':
            id_venta = data.get('id')
            if not id_venta:
                result = {'error': 'Falta ID de venta'}
            else:
                result = pagar_venta(id_venta)
        else:
            result = {'error': f'Acción desconocida: {action}'}
        
        print(json.dumps(result))
    except json.JSONDecodeError as e:
        print(json.dumps({'error': f'Error decodificando JSON: {str(e)}'}))
    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == '__main__':
    main()
