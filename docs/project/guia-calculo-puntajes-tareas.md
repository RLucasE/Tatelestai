# Guía Práctica: Cálculo de Puntajes de Priorización de Tareas

> **Objetivo:** Procedimiento conciso y estandarizado para calcular el puntaje numérico de una tarea o requerimiento antes de decidir su orden en el backlog.

---

## 1. Método RICE (Estándar de Producto)

Fórmula matemática para obtener un puntaje objetivo comparable entre funcionalidades:

$$\text{Puntaje RICE} = \frac{\text{Reach} \times \text{Impact} \times \text{Confidence}}{\text{Effort}}$$

### Valores y Escalas

| Variable | Definición | Escala de Valores |
|---|---|---|
| **Reach (R)** | ¿A cuántos usuarios o transacciones impacta en un período (ej. por mes o sprint)? | Número absoluto estimado (ej. `50`, `200`, `1000`). |
| **Impact (I)** | ¿Cuánto valor aporta al objetivo de negocio o al usuario? | • `3` = Masivo<br>• `2` = Alto<br>• `1` = Medio<br>• `0.5` = Bajo<br>• `0.25` = Mínimo |
| **Confidence (C)** | Nivel de certeza sobre las estimaciones de alcance, impacto y esfuerzo. | • `1.0` (100%) = Certeza alta (datos reales, requerimiento validado)<br>• `0.8` (80%) = Certeza media (estimación sólida)<br>• `0.5` (50%) = Certeza baja (hipótesis / incertidumbre técnica) |
| **Effort (E)** | Tiempo/recursos requeridos (semanas-persona o sprints). | • `0.5` = 2 a 3 días<br>• `1` = 1 semana<br>• `2` = 2 semanas<br>• `3+` = 3 o más semanas |

---

### Ejemplo de Cálculo RICE

* **Tarea:** *Validación de código de retiro en mostrador con escaneo de QR*.
  * **R** = 150 retiros/mes
  * **I** = 2 (Impacto alto en la operación)
  * **C** = 0.8 (80% confianza técnica)
  * **E** = 1 semana
  
$$\text{Puntaje RICE} = \frac{150 \times 2 \times 0.8}{1} = \mathbf{240}$$

---

## 2. Método Rápido: Valor vs. Complejidad (Escala 1 a 10)

Recomendado para tareas internas de arquitectura, refactorizaciones o bugs donde el cálculo de usuarios (*Reach*) no aplica directamente.

$$\text{Prioridad} = \frac{\text{Valor (1-10)}}{\text{Complejidad (1-10)}}$$

### Criterio de Puntuación (1 al 10)

* **Valor (Beneficio técnico / de negocio):**
  * `9 - 10`: Crítico. Bloquea el funcionamiento, previene pérdida de datos o es requisito legal/aprobación.
  * `6 - 8`: Alto. Optimiza flujos principales o reduce deuda técnica severa.
  * `3 - 5`: Moderado. Mejora estética o funcionalidad complementaria.
  * `1 - 2`: Cosmético o prescindible.

* **Complejidad (Esfuerzo + Incertidumbre):**
  * `1 - 3`: Simple. Tarea bien conocida, cambios puntuales en 1 o 2 archivos, bajo riesgo.
  * `4 - 6`: Media. Afecta múltiples capas (Backend + Frontend + Base de datos), requiere migraciones o lógica de negocio no trivial.
  * `7 - 8`: Alta. Involucra concurrencia, transacciones distribuidas, motores externos o seguridad crítica.
  * `9 - 10`: Muy alta. Requiere rediseño arquitectural o investigación profunda no resuelta.

---

## 3. Criterio de Decisión y Orden de Ejecución

1. **Puntaje > 1.5** (Alto Valor / Baja Complejidad): **Quick Wins** $\rightarrow$ Ejecutar de inmediato.
2. **Puntaje entre 0.8 y 1.4** (Equilibrado): Planificar en el sprint actual.
3. **Puntaje < 0.8** (Bajo Valor / Alta Complejidad): Descartar, posponer o dividir la tarea en subtareas más pequeñas.

---

## 4. Plantilla de Evaluación Rápida (Copiar y Pegar)

```markdown
### Evaluación de Tarea: [Nombre de la Tarea]

- **Método RICE:**
  - Reach (R): 
  - Impact (I): [3 / 2 / 1 / 0.5 / 0.25]
  - Confidence (C): [1.0 / 0.8 / 0.5]
  - Effort (E): [semanas]
  - **Puntaje RICE = (R * I * C) / E = [resultado]**

- **Método Valor/Complejidad (Alternativo):**
  - Valor (1-10): 
  - Complejidad (1-10): 
  - **Ratio (Valor / Complejidad) = [resultado]**
```
