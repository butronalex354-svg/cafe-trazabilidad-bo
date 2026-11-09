# Frontend (Quasar): se compila y se sirve como sitio estatico.
# En Railway: Root Directory = /frontend y variable API_URL = direccion publica del backend.
FROM node:24-alpine AS compilar
WORKDIR /app
COPY . .
RUN npm ci
# Railway entrega las variables del servicio como ARG al compilar
ARG API_URL
ENV API_URL=$API_URL
RUN npx quasar build

FROM node:24-alpine
RUN npm install -g serve@14.2.4
WORKDIR /app
COPY --from=compilar /app/dist/spa ./sitio
# Railway define PORT; en la compu se usa 3000
CMD ["sh", "-c", "serve -s sitio -l tcp://0.0.0.0:${PORT:-3000}"]
