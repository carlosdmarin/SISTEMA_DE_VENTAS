#!/usr/bin/env python3
import sys
import json
import os
from supabase import create_client, Client
from datetime import datetime
import pytz

# Configurar zona horaria de Perú
LIMA_TZ = pytz.timezone('America/Lima')

# Configuración de Supabase - Usar variables de entorno en Render
SUPABASE_URL = os.environ.get('SUPABASE_URL', 'https://tu-proyecto.supabase.co')
SUPABASE_KEY = os.environ.get('SUPABASE_KEY', 'tu-anon-key')

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def get_today(fecha):
    """Obtener ventas de una fecha específica"""
    try:
        result = supabase.table('ventas')\
            .select('*')\
            .eq('fecha_registro', fecha)\
            .execute()
        return result.data
    except Exception as e:
        return {'error': str(e)}

def get_by_date_range(desde, hasta):
    """Obtener ventas en un rango de fechas"""
    try:
        # Usar between para el rango de fechas
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
        # Asegurar que la fecha_registro tenga hora local
        if 'fecha_registro' not in venta:
            venta['fecha_registro'] = datetime.now(LIMA_TZ).strftime('%Y-%m-%d %H:%M:%S')
        
        result = supabase.table('ventas').insert(venta).execute()
        if result.data:
            return {'id_venta': result.data[0]['id_venta'], 'success': True}
        return {'error': 'No se pudo insertar'}
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
        if result.data:
            return {'success': True}
        return {'success': False}
    except Exception as e:
        print(f"Error en pagar_venta: {e}", file=sys.stderr)
        return {'success': False}

def main():
    try:
        # Leer datos de entrada
        input_data = sys.stdin.read()
        data = json.loads(input_data)
        action = data.get('action')
        
        if action == 'get_today':
            fecha = data.get('fecha')
            result = get_today(fecha)
        elif action == 'get_by_date_range':
            desde = data.get('desde')
            hasta = data.get('hasta')
            result = get_by_date_range(desde, hasta)
        elif action == 'create':
            venta = data.get('venta', {})
            result = create_venta(venta)
        elif action == 'pay':
            id_venta = data.get('id')
            result = pagar_venta(id_venta)
        else:
            result = {'error': f'Acción desconocida: {action}'}
        
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == '__main__':
    main()
