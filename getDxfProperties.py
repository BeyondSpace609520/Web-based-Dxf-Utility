from sqlalchemy import create_engine
import sys
import pymysql
import pandas as pd
import ezdxf
import math
import os
import dxfgrabber
import numpy as np
from dxf2svg.pycore import save_svg_from_dxf, extract_all

tableName = "dxfutiliy"
sqlEngine = create_engine('mysql+pymysql://root:@127.0.0.1/test', pool_recycle=3600)
dbConnection = sqlEngine.connect()

xlsxSrc = sys.argv[1] # 'ZH053v40_lista.xlsx'
dxfDir = sys.argv[2] # './SMCSMK300096'
moduleName = sys.argv[3] #USERCode
svgDir = 'svg/' + moduleName
dxfLengths = []

if not os.path.exists(svgDir):
    os.makedirs(svgDir)

def getLengthFromDxf(dxfSrc):
    dwg = ezdxf.readfile(dxfSrc)
    msp = dwg.modelspace()
    longitud_total = 0
    
    for e in msp:
        if e.dxftype() == 'LINE':
            dl = math.sqrt((e.dxf.start[0]-e.dxf.end[0])**2 + (e.dxf.start[1]- 
            e.dxf.end[1])**2)
            longitud_total = longitud_total + dl
        elif e.dxftype() == 'CIRCLE':
            dc = 2*math.pi*e.dxf.radius
            longitud_total = longitud_total + dc
        elif e.dxftype() == 'SPLINE':
            puntos = e.get_control_points()
            for i in range(len(puntos)-1):
                ds = math.sqrt((puntos[i][0]-puntos[i+1][0])**2 + (puntos[i][1]- 
                puntos[i+1][1])**2)  
                longitud_total = longitud_total + ds
    
    return longitud_total

data = pd.read_excel( xlsxSrc, sheet_name=0)

for fname in os.listdir(dxfDir):
    if fname.endswith(".dxf"):
        dxfLengths.append( getLengthFromDxf(os.path.join(dxfDir, fname)) )
        
        save_svg_from_dxf(
            os.path.join(dxfDir, fname), 
            svgfilepath=os.path.join(svgDir, fname)+'.svg', 
            frame_name=None, size=60)
        
data['Perimeter'] = dxfLengths      

maxDimensionX = []
maxDimensionY = []

for fname in os.listdir(dxfDir):
    if fname.endswith(".dxf"):
        dxf = dxfgrabber.readfile(os.path.join(dxfDir, fname))
        shapes = dxf.entities.get_entities()
        minX, maxX, minY, maxY = 999999,0,999999,0
        for shape in shapes:
            if shape.dxftype == 'LINE':
                x, y = shape.start[0], shape.start[1]
                if x < minX:
                    minX = x
                if y < minY:
                    minY = y
                if x >= maxX:
                    maxX = x
                if y >= maxY:
                    maxY = y
                x, y = shape.end[0], shape.end[1]
                if x < minX:
                    minX = x
                if y < minY:
                    minY = y
                if x >= maxX:
                    maxX = x
                if y >= maxY:
                    maxY = y
            if shape.dxftype == 'ARC':
                x, y = shape.center[0], shape.center[1]
                if x < minX:
                    minX = x
                if y < minY:
                    minY = y
                if x >= maxX:
                    maxX = x
                if y >= maxY:
                    maxY = y
        maxDimensionX.append(maxX-minX)
        maxDimensionY.append(maxY-minY)

data['Width'] = maxDimensionX  
data['Height'] = maxDimensionY  
data['ModuleName'] = moduleName

data.to_sql(tableName, dbConnection, if_exists='append');