# 🏠 Home Finance Tracker — Plan de Infraestructura Laravel

## Objetivo

Construir una aplicación web en Laravel que permita registrar ingresos y egresos del hogar, con visibilidad tanto por persona como a nivel global del hogar.

---

## 🧱 Entidades Principales

### 1. `Household` (Hogar)
Representa la unidad familiar. Todo está agrupado bajo un hogar.

- Nombre del hogar
- Fecha de creación

### 2. `Member` (Miembro)
Cada persona que pertenece al hogar y puede tener sus propias transacciones.

- Nombre
- Puede estar vinculado o no a un usuario del sistema (cuenta de acceso)
- Pertenece a un hogar

### 3. `Category` (Categoría)
Clasifica las transacciones para un mejor análisis.

- Nombre (ej: Sueldo, Arriendo, Supermercado, Salud)
- Tipo: ingreso o egreso
- Ícono / color para visualización

### 4. `Transaction` (Transacción)
El corazón del sistema. Registra cada movimiento de dinero.

- Pertenece a un miembro y a un hogar
- Tiene una categoría
- Tipo: ingreso o egreso
- Monto
- Descripción opcional
- Fecha
- Opción de recurrencia (mensual, semanal, etc.)

---

## 🔗 Relaciones

```
Household
  └── tiene muchos Members
  └── tiene muchas Transactions (vía Members)

Member
  └── pertenece a un Household
  └── tiene muchas Transactions
  └── puede estar vinculado a un User (login)

Transaction
  └── pertenece a un Member
  └── pertenece a una Category
  └── tiene un tipo: income / expense
```

---

## 📊 Dashboard Principal

El dashboard se organiza en secciones apiladas verticalmente, cada una con un propósito claro.

---

### 🌊 Sección 1 — Curva Anual (Saldo Corriente)

Un gráfico de línea suavizada (tipo senoidal) que recorre los 365 días del año en curso. Representa el **saldo acumulado día a día**: cada punto es el resultado de sumar todos los ingresos y restar todos los egresos desde el 1 de enero hasta ese día.

**Comportamiento interactivo:**
- Cada punto del eje X es un día seleccionable
- Al hacer clic o hover sobre un día, se despliega un panel lateral o tooltip con el **listado de transacciones registradas ese día** (descripción, categoría, miembro, monto)
- Los días sin movimientos se muestran con el mismo saldo que el día anterior (línea plana)
- Se marca visualmente el día actual con un indicador destacado
- La curva cambia de color si el saldo acumulado cae bajo cero (zona negativa en rojo, positiva en verde o azul)

---

### 📅 Sección 2 — Gestión Mensual

Un selector de mes permite navegar entre los meses del año. Al seleccionar un mes se actualiza toda la sección con:

- **Resumen de ingresos del mes** (total consolidado del hogar)
- **Resumen de egresos del mes** (total consolidado del hogar)
- **Balance neto del mes** con indicador visual positivo/negativo
- Una **tabla comparativa por miembro** mostrando cuánto ingresó y cuánto gastó cada persona ese mes

---

### 💰 Sección 3 — Ingresos vs Egresos del Mes

Visualización clara del contraste entre lo que entra y lo que sale:

- Dos columnas o barras enfrentadas: **Total Ingresos** vs **Total Egresos**
- Indicador del **balance resultante** con color según signo
- Listado detallado de todas las transacciones del mes, agrupadas por tipo (primero ingresos, luego egresos), con filtro por miembro

---

### 🍩 Sección 4 — Gráficos por Categoría

Tres representaciones simultáneas de la distribución del gasto e ingreso por categoría:

**Gráfico de dona:** muestra la proporción visual de cada categoría sobre el total del mes. Se puede alternar entre vista de ingresos y vista de egresos.

**Barras horizontales (ranking):** lista ordenada de mayor a menor de las categorías con más movimiento. Incluye barra de progreso relativa al total y monto absoluto.

**Tarjetas por categoría:** una tarjeta por cada categoría activa en el mes, mostrando nombre, ícono, monto total y porcentaje que representa sobre el total. Las tarjetas de egresos se ordenan de mayor a menor consumo.

---

### 🔍 Sección 5 — Top Consumos

Enfocada exclusivamente en los **egresos de mayor impacto**:

- **Top 5 categorías** con más gasto del mes en tarjetas destacadas
- **Top 5 transacciones individuales** (los egresos más altos del mes)
- Comparativa con el mes anterior para cada categoría (flecha arriba/abajo con porcentaje de variación)

---

### 👤 Vista por Persona

Accesible desde el dashboard haciendo clic en el nombre o avatar de un miembro:

- Resumen personal del mes (ingresos, egresos, balance)
- Curva anual individual (saldo corriente solo de esa persona)
- Historial de transacciones personales con filtro por mes y categoría
- Gráfico de dona y tarjetas de categorías propias

---

## 🗂️ Módulos de la Aplicación

| Módulo | Descripción |
|---|---|
| **Auth** | Registro e inicio de sesión de usuarios |
| **Household** | Crear y gestionar el hogar familiar |
| **Members** | Agregar/editar miembros del hogar |
| **Transactions** | CRUD completo de ingresos y egresos |
| **Categories** | Gestión de categorías personalizadas |
| **Reports** | Dashboards de resumen por persona y por hogar |

---

## 🔐 Control de Acceso (Roles sugeridos)

- **Admin del hogar:** puede ver todo, gestionar miembros y categorías
- **Miembro:** solo ve y gestiona sus propias transacciones
- **Solo lectura:** puede consultar reportes pero no registrar

---

## 🚦 Flujo de Uso

1. Un usuario crea un **hogar** y se convierte en su administrador
2. Agrega **miembros** (otros usuarios o solo nombres sin cuenta)
3. Define **categorías** de ingresos y egresos
4. Cada miembro registra sus **transacciones** diarias
5. Desde el dashboard se visualiza el **resumen por persona** y el **total del hogar**

---

## 🛠️ Stack Tecnológico Recomendado

- **Backend:** Laravel 11+
- **Base de datos:** MySQL o PostgreSQL
- **Frontend:** Livewire + Alpine.js (o Inertia.js + Vue/React si se prefiere SPA)
- **Autenticación:** Laravel Breeze o Jetstream
- **Gráficos:** Chart.js o Recharts
- **Estilos:** Tailwind CSS

---

## 📝 Diseño de Formularios

### Formulario de Ingreso
| Campo | Tipo de input | Detalle |
|---|---|---|
| **Miembro** | Selector (dropdown) | Lista de miembros del hogar |
| **Categoría** | Selector (dropdown) | Filtrado solo por categorías de tipo ingreso |
| **Monto** | Campo numérico libre | Con símbolo de moneda |
| **Fecha** | Selector de calendario | Por defecto: fecha de hoy |
| **Descripción** | Texto libre opcional | Para notas adicionales |
| **¿Es recurrente?** | Toggle / checkbox | Si se activa, muestra selector de frecuencia |

### Formulario de Egreso
| Campo | Tipo de input | Detalle |
|---|---|---|
| **Miembro** | Selector (dropdown) | Lista de miembros del hogar |
| **Categoría** | Selector (dropdown) | Filtrado solo por categorías de tipo egreso |
| **Monto** | Campo numérico libre | Con símbolo de moneda |
| **Fecha** | Selector de calendario | Por defecto: fecha de hoy |
| **Descripción** | Texto libre opcional | Para notas adicionales |
| **¿Es recurrente?** | Toggle / checkbox | Si se activa, muestra selector de frecuencia |

> Ambos formularios son independientes y se acceden desde botones distintos en el dashboard (ej: **"+ Agregar Ingreso"** y **"+ Agregar Egreso"**), lo que reduce errores de tipo al registrar.

### Comportamiento esperado de los selectores

- **Miembro:** muestra nombre y avatar de cada persona del hogar. Si el usuario autenticado es un miembro, puede preseleccionarse automáticamente.
- **Categoría:** se filtra automáticamente según el tipo de formulario (ingresos o egresos), evitando selecciones incorrectas.
- **Fecha:** selector de calendario con navegación por mes, bloqueando fechas futuras opcionalmente.
- **Recurrencia:** solo visible si se activa el toggle. Opciones: diaria, semanal, mensual, anual.

---

## 📌 Consideraciones Adicionales

- Soporte multi-moneda a futuro
- Exportación de reportes en PDF o Excel
- Notificaciones cuando se supera el presupuesto de una categoría
- Módulo de presupuestos mensuales por categoría o por miembro