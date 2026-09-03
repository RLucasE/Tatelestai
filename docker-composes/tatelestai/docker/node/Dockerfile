# ──────────────────────────────────────────────
# Base: instala dependencias compartidas
# Build context principal: directorio del compose (tatelestai/)
# Contexto adicional "frontend": ../../Frontend/vue-project
# ──────────────────────────────────────────────
FROM node:22-alpine AS base

WORKDIR /app

# Copia los manifiestos desde el contexto adicional "frontend"
COPY --from=frontend package.json package-lock.json ./

RUN npm ci

# ──────────────────────────────────────────────
# Development: dev-server de Vite con HMR
# Nota: en desarrollo, el código se monta como volumen (docker-compose.yml),
# por lo que este COPY es solo un fallback si se construye sin compose.
# ──────────────────────────────────────────────
FROM base AS development

COPY ./docker/node/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY --from=frontend . .

EXPOSE 3000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# --host 0.0.0.0 es necesario para que Vite sea accesible desde fuera del contenedor
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ──────────────────────────────────────────────
# Builder: compila los assets estáticos
# ──────────────────────────────────────────────
FROM base AS builder

COPY --from=frontend . .

RUN npm run build

# ──────────────────────────────────────────────
# Production: sirve los assets compilados con Nginx
# ──────────────────────────────────────────────
FROM nginx:alpine AS production

# Copia la configuración de Nginx para SPA (vue-router)
# Este archivo viene del contexto principal (directorio del compose)
COPY ./docker/node/nginx-spa.conf /etc/nginx/conf.d/default.conf

# Copia los assets compilados desde el stage builder
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
