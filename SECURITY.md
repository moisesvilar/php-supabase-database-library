# 🔒 Guía de Seguridad

## ⚠️ CREDENCIALES EXPUESTAS - ACCIÓN INMEDIATA REQUERIDA

Si has subido credenciales a GitHub por accidente, sigue estos pasos **INMEDIATAMENTE**:

### 1. Rotar Credenciales en Supabase

1. **Ve a tu [Dashboard de Supabase](https://supabase.com/dashboard)**
2. **Selecciona tu proyecto**
3. **Ve a Settings → Database**
4. **Haz clic en "Reset database password"**
5. **Genera una nueva contraseña segura**
6. **Guarda la nueva contraseña de forma segura**

### 2. Actualizar tu Configuración Local

```bash
# Actualiza tu archivo .env con la nueva contraseña
nano .env

# O usa el script de configuración
php setup.php
```

### 3. Limpiar Historial de Git (Opcional pero Recomendado)

```bash
# ⚠️ CUIDADO: Esto reescribe el historial de Git
git filter-branch --force --index-filter \
'git rm --cached --ignore-unmatch config.php example_supabase.php' \
--prune-empty --tag-name-filter cat -- --all

# Forzar push (solo si es tu repositorio personal)
git push origin --force --all
```

### 4. Verificar que las Credenciales Están Seguras

```bash
# Verificar que .env está en .gitignore
grep -n "\.env" .gitignore

# Verificar que no hay credenciales hardcodeadas
grep -r "supabase.co" src/ --exclude-dir=vendor
grep -r "password" src/ --exclude-dir=vendor
```

## 🛡️ Mejores Prácticas de Seguridad

### Variables de Entorno

```php
// ✅ CORRECTO - Usar variables de entorno
use DatabaseLibrary\Utils\EnvLoader;

$db = DatabaseManager::createSupabaseFromUrl(
    EnvLoader::required('SUPABASE_URL'),
    EnvLoader::required('SUPABASE_PASSWORD')
);

// ❌ INCORRECTO - Credenciales hardcodeadas
$db = DatabaseManager::createSupabaseFromUrl(
    'postgresql://postgres:password123@db.example.supabase.co:5432/postgres',
    'password123'
);
```

### Configuración de Producción

```env
# .env para producción
SUPABASE_HOST=db.your-project.supabase.co
SUPABASE_PASSWORD=super-secure-password-with-symbols-123!@#
SUPABASE_URL=postgresql://postgres:super-secure-password-with-symbols-123!@#@db.your-project.supabase.co:5432/postgres

# Configuración de logging para producción
LOG_LEVEL=ERROR
LOG_FILE=/var/log/app/database.log
APP_ENV=production
```

### Validación de Entrada

```php
// ✅ CORRECTO - Validación automática
$queryBuilder = $db->queryBuilder('users');
$query = $queryBuilder
    ->where('email', '=', $userEmail) // Automáticamente sanitizado
    ->buildSelect();

// ✅ CORRECTO - Prepared statements
$users = $db->executeQuery('SELECT * FROM users WHERE email = ?', [$userEmail]);

// ❌ INCORRECTO - Concatenación directa
$query = "SELECT * FROM users WHERE email = '{$userEmail}'"; // Vulnerable a SQL injection
```

## 🔐 Gestión de Credenciales

### Desarrollo Local

```bash
# Crear .env desde plantilla
cp env.example .env

# Editar con credenciales reales
nano .env

# Verificar que está en .gitignore
cat .gitignore | grep .env
```

### Servidores de Producción

```bash
# Opción 1: Variables de entorno del sistema
export SUPABASE_HOST="db.your-project.supabase.co"
export SUPABASE_PASSWORD="your-secure-password"

# Opción 2: Archivo .env en directorio seguro
sudo mkdir -p /etc/myapp
sudo cp .env /etc/myapp/.env
sudo chmod 600 /etc/myapp/.env
sudo chown www-data:www-data /etc/myapp/.env
```

### Docker

```dockerfile
# Dockerfile
FROM php:8.1-cli

# Variables de entorno (usar secrets en producción)
ENV SUPABASE_HOST=db.your-project.supabase.co
ENV LOG_LEVEL=ERROR
ENV APP_ENV=production

# No incluir SUPABASE_PASSWORD aquí, usar Docker secrets
COPY . /app
WORKDIR /app

CMD ["php", "your-app.php"]
```

```bash
# Docker Compose con secrets
version: '3.8'
services:
  app:
    build: .
    environment:
      - SUPABASE_HOST=db.your-project.supabase.co
    secrets:
      - supabase_password
    
secrets:
  supabase_password:
    file: ./secrets/supabase_password.txt
```

## 🚨 Detección de Problemas de Seguridad

### Script de Verificación

```bash
#!/bin/bash
# security-check.sh

echo "🔍 Verificación de seguridad..."

# Verificar que .env está en .gitignore
if ! grep -q "\.env" .gitignore; then
    echo "❌ .env no está en .gitignore"
    exit 1
fi

# Buscar credenciales hardcodeadas
if grep -r "supabase\.co" src/ --exclude-dir=vendor >/dev/null 2>&1; then
    echo "❌ Posibles credenciales hardcodeadas encontradas"
    grep -r "supabase\.co" src/ --exclude-dir=vendor
    exit 1
fi

# Verificar permisos de .env
if [ -f .env ]; then
    PERMS=$(stat -c "%a" .env)
    if [ "$PERMS" != "600" ]; then
        echo "⚠️  Permisos de .env no son seguros (actual: $PERMS, recomendado: 600)"
        chmod 600 .env
        echo "✅ Permisos corregidos"
    fi
fi

echo "✅ Verificación de seguridad completada"
```

### Monitoreo de Logs

```php
// Configurar logging seguro
$logger = new Logger(
    '/var/log/app/database.log',  // Archivo fuera del webroot
    true,                         // Habilitado
    'ERROR',                      // Solo errores en producción
    50 * 1024 * 1024,            // 50MB max
    10                            // 10 archivos de rotación
);

// No loggear datos sensibles
$db->setLogger($logger);
```

## 🔄 Rotación de Credenciales

### Proceso Recomendado

1. **Generar nuevas credenciales** en Supabase
2. **Actualizar variables de entorno** en todos los entornos
3. **Reiniciar aplicaciones** para cargar nuevas credenciales
4. **Verificar funcionamiento** con health checks
5. **Revocar credenciales antiguas**

### Script de Rotación

```bash
#!/bin/bash
# rotate-credentials.sh

echo "🔄 Iniciando rotación de credenciales..."

# Backup de configuración actual
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

echo "1. Ve a Supabase Dashboard y genera nueva contraseña"
echo "2. Ingresa la nueva contraseña:"
read -s NEW_PASSWORD

# Actualizar .env
sed -i "s/SUPABASE_PASSWORD=.*/SUPABASE_PASSWORD=$NEW_PASSWORD/" .env
sed -i "s/:.*@/:$NEW_PASSWORD@/" .env

echo "✅ Credenciales actualizadas"
echo "🧪 Probando conexión..."

# Probar nueva conexión
php -r "
require 'vendor/autoload.php';
use DatabaseLibrary\DatabaseManager;
use DatabaseLibrary\Utils\EnvLoader;

try {
    EnvLoader::load();
    \$db = DatabaseManager::createSupabaseFromUrl(
        EnvLoader::required('SUPABASE_URL'),
        EnvLoader::required('SUPABASE_PASSWORD')
    );
    \$db->connect();
    echo '✅ Nueva conexión exitosa\n';
    \$db->disconnect();
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo "🎉 Rotación completada exitosamente"
```

## 📋 Checklist de Seguridad

### Antes de Subir a GitHub

- [ ] `.env` está en `.gitignore`
- [ ] No hay credenciales hardcodeadas en el código
- [ ] Se usa `EnvLoader` para todas las credenciales
- [ ] Los archivos de ejemplo no contienen datos reales
- [ ] Se ha ejecutado el script de verificación de seguridad

### En Producción

- [ ] Variables de entorno configuradas en el servidor
- [ ] Archivo `.env` con permisos 600
- [ ] Logging configurado apropiadamente
- [ ] Credenciales rotadas regularmente
- [ ] Monitoreo de accesos activado
- [ ] Backups seguros configurados

### Monitoreo Continuo

- [ ] Alertas por intentos de acceso fallidos
- [ ] Logs de queries sospechosas
- [ ] Verificación periódica de credenciales
- [ ] Auditoría de accesos a la base de datos

## 🆘 En Caso de Compromiso

1. **Cambiar inmediatamente** todas las credenciales
2. **Revisar logs** para detectar accesos no autorizados
3. **Notificar** al equipo de seguridad
4. **Documentar** el incidente
5. **Implementar** medidas adicionales de seguridad

---

**Recuerda**: La seguridad es responsabilidad de todos. Siempre es mejor prevenir que lamentar. 🛡️
