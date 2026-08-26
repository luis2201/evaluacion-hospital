#!/usr/bin/env bash
set -Eeuo pipefail

APP_URL="${APP_URL:?Define APP_URL, por ejemplo https://evaluacion.example.edu.ec}"
curl --fail --silent --show-error --location --max-time 15 "$APP_URL/up" >/dev/null
curl --fail --silent --show-error --location --max-time 15 "$APP_URL/iniciar-sesion" | grep -qi '<html'
echo "Aplicación disponible en $APP_URL"
