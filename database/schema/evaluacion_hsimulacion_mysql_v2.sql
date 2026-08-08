-- =============================================================
-- Sistema de Evaluación del Hospital de Simulación
-- Esquema MySQL 8.0 - Versión 2
-- Base funcional para Laravel monolítico
-- Alcance: evaluar un único hospital; no administra hospitales, centros,
-- salas, estudiantes, docentes, equipos ni escenarios individuales.
-- =============================================================

CREATE DATABASE IF NOT EXISTS evaluacion_hsimulacion
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE evaluacion_hsimulacion;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- 1. USUARIOS Y AUTORIZACIÓN
-- =============================================================

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_roles_codigo (codigo)
) ENGINE=InnoDB;

CREATE TABLE role_user (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_role_user_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_role_user_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tablas Laravel de recuperación de contraseña y sesiones.
CREATE TABLE password_reset_tokens (
    email VARCHAR(150) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    KEY idx_sessions_user (user_id),
    KEY idx_sessions_last_activity (last_activity),
    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- 2. MODELO / INSTRUMENTO DE EVALUACIÓN VERSIONADO
-- =============================================================

CREATE TABLE modelos_evaluacion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(180) NOT NULL,
    version VARCHAR(30) NOT NULL,
    descripcion TEXT NULL,
    estado ENUM('BORRADOR','PUBLICADO','ARCHIVADO') NOT NULL DEFAULT 'BORRADOR',
    publicado_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_modelos_nombre_version (nombre, version)
) ENGINE=InnoDB;

CREATE TABLE dominios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modelo_evaluacion_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    peso DECIMAL(5,2) NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_dominios_modelo_codigo (modelo_evaluacion_id, codigo),
    UNIQUE KEY uk_dominios_modelo_orden (modelo_evaluacion_id, orden),
    CONSTRAINT chk_dominios_peso CHECK (peso > 0 AND peso <= 100),
    CONSTRAINT fk_dominios_modelo
        FOREIGN KEY (modelo_evaluacion_id) REFERENCES modelos_evaluacion(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE criterios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dominio_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_criterios_dominio_codigo (dominio_id, codigo),
    UNIQUE KEY uk_criterios_dominio_orden (dominio_id, orden),
    CONSTRAINT fk_criterios_dominio
        FOREIGN KEY (dominio_id) REFERENCES dominios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- El descriptor es la unidad evaluable de la ficha.
-- En la interfaz puede presentarse como "evidencia evaluable".
CREATE TABLE descriptores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    criterio_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    descripcion TEXT NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    puntaje_maximo TINYINT UNSIGNED NOT NULL DEFAULT 2,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_descriptores_criterio_codigo (criterio_id, codigo),
    UNIQUE KEY uk_descriptores_criterio_orden (criterio_id, orden),
    CONSTRAINT chk_descriptor_puntaje_maximo CHECK (puntaje_maximo = 2),
    CONSTRAINT fk_descriptores_criterio
        FOREIGN KEY (criterio_id) REFERENCES criterios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE categorias_resultado (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modelo_evaluacion_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    porcentaje_desde DECIMAL(5,2) NOT NULL,
    porcentaje_hasta DECIMAL(5,2) NOT NULL,
    interpretacion TEXT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT chk_categoria_rango CHECK (
        porcentaje_desde >= 0 AND
        porcentaje_hasta <= 100 AND
        porcentaje_desde <= porcentaje_hasta
    ),
    UNIQUE KEY uk_categorias_modelo_orden (modelo_evaluacion_id, orden),
    CONSTRAINT fk_categorias_modelo
        FOREIGN KEY (modelo_evaluacion_id) REFERENCES modelos_evaluacion(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =============================================================
-- 3. PROCESO DE EVALUACIÓN
-- =============================================================

CREATE TABLE evaluaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modelo_evaluacion_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    tipo_escenario ENUM('CLINICA','QUIRURGICA','MIXTA','OTRA') NULL,
    fecha_inicio DATE NULL,
    fecha_limite_carga DATE NULL,
    fecha_inicio_evaluacion DATE NULL,
    fecha_cierre DATE NULL,
    estado ENUM('BORRADOR','CARGA_EVIDENCIAS','EN_EVALUACION','CERRADA','CANCELADA')
        NOT NULL DEFAULT 'BORRADOR',
    creada_por BIGINT UNSIGNED NOT NULL,
    cerrada_por BIGINT UNSIGNED NULL,
    cerrada_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_evaluaciones_codigo (codigo),
    KEY idx_evaluaciones_estado (estado),
    CONSTRAINT fk_evaluaciones_modelo
        FOREIGN KEY (modelo_evaluacion_id) REFERENCES modelos_evaluacion(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_creador
        FOREIGN KEY (creada_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_cerrador
        FOREIGN KEY (cerrada_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Un responsable por dominio dentro de cada proceso de evaluación.
CREATE TABLE evaluacion_dominios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    dominio_id BIGINT UNSIGNED NOT NULL,
    responsable_id BIGINT UNSIGNED NOT NULL,
    estado ENUM('PENDIENTE','EN_CARGA','ENVIADO','OBSERVADO','COMPLETO','CERRADO')
        NOT NULL DEFAULT 'PENDIENTE',
    enviado_at TIMESTAMP NULL,
    completado_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_evaluacion_dominio (evaluacion_id, dominio_id),
    KEY idx_eval_dom_responsable (responsable_id),
    CONSTRAINT fk_eval_dom_evaluacion
        FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_eval_dom_dominio
        FOREIGN KEY (dominio_id) REFERENCES dominios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_eval_dom_responsable
        FOREIGN KEY (responsable_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Reporte de autoevaluación previo: una reflexión de máximo 250 palabras
-- por dominio. No interviene en el cálculo de la calificación.
CREATE TABLE autoevaluaciones_dominios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_dominio_id BIGINT UNSIGNED NOT NULL,
    contenido TEXT NOT NULL,
    cantidad_palabras SMALLINT UNSIGNED NOT NULL,
    estado ENUM('BORRADOR','ENVIADA') NOT NULL DEFAULT 'BORRADOR',
    registrada_por BIGINT UNSIGNED NOT NULL,
    enviada_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_autoevaluacion_dominio (evaluacion_dominio_id),
    CONSTRAINT chk_autoevaluacion_palabras CHECK (cantidad_palabras <= 250),
    CONSTRAINT fk_autoevaluacion_dominio
        FOREIGN KEY (evaluacion_dominio_id) REFERENCES evaluacion_dominios(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_autoevaluacion_usuario
        FOREIGN KEY (registrada_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Aunque inicialmente exista un solo agente externo, la relación permite ampliarlo.
CREATE TABLE evaluacion_evaluadores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    evaluador_id BIGINT UNSIGNED NOT NULL,
    es_principal BOOLEAN NOT NULL DEFAULT TRUE,
    asignado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_evaluacion_evaluador (evaluacion_id, evaluador_id),
    CONSTRAINT fk_eval_evaluador_evaluacion
        FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_eval_evaluador_user
        FOREIGN KEY (evaluador_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Instancia de cada descriptor/evidencia evaluable dentro del proceso.
-- NULL = sin calificar; 0 = no cumple; 1 = cumple parcialmente; 2 = cumple.
CREATE TABLE evaluacion_descriptores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    descriptor_id BIGINT UNSIGNED NOT NULL,
    estado ENUM('PENDIENTE','EN_EVALUACION','OBSERVADO','EVALUADO')
        NOT NULL DEFAULT 'PENDIENTE',
    calificacion TINYINT UNSIGNED NULL,
    observacion_evaluador TEXT NULL,
    evaluado_por BIGINT UNSIGNED NULL,
    evaluado_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_eval_descriptor (evaluacion_id, descriptor_id),
    KEY idx_eval_desc_estado (evaluacion_id, estado),
    KEY idx_eval_desc_calificacion (evaluacion_id, calificacion),
    CONSTRAINT chk_eval_desc_calificacion CHECK (
        calificacion IS NULL OR calificacion IN (0,1,2)
    ),
    CONSTRAINT fk_eval_desc_evaluacion
        FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_eval_desc_descriptor
        FOREIGN KEY (descriptor_id) REFERENCES descriptores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_eval_desc_evaluador
        FOREIGN KEY (evaluado_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- 4. ARCHIVOS DE EVIDENCIA
-- =============================================================

-- El descriptor es la evidencia evaluable definida por el instrumento.
-- Cada instancia admite uno o varios archivos privados e independientes.
CREATE TABLE descriptor_archivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_descriptor_id BIGINT UNSIGNED NOT NULL,
    descripcion TEXT NULL,
    disco VARCHAR(50) NOT NULL DEFAULT 'private',
    ruta VARCHAR(1000) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_almacenado VARCHAR(255) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    extension VARCHAR(20) NULL,
    tamano_bytes BIGINT UNSIGNED NOT NULL,
    hash_sha256 CHAR(64) NOT NULL,
    cargado_por BIGINT UNSIGNED NOT NULL,
    eliminado_por BIGINT UNSIGNED NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY idx_descriptor_archivos_descriptor (evaluacion_descriptor_id),
    UNIQUE KEY uk_descriptor_archivo_hash (evaluacion_descriptor_id, hash_sha256),
    CONSTRAINT chk_descriptor_archivo_tamano CHECK (tamano_bytes > 0),
    CONSTRAINT fk_descriptor_archivos_descriptor
        FOREIGN KEY (evaluacion_descriptor_id) REFERENCES evaluacion_descriptores(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_descriptor_archivos_cargador
        FOREIGN KEY (cargado_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_descriptor_archivos_eliminador
        FOREIGN KEY (eliminado_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE descriptor_enlaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_descriptor_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(2048) NOT NULL,
    descripcion VARCHAR(500) NULL,
    registrado_por BIGINT UNSIGNED NOT NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY idx_descriptor_enlaces_descriptor (evaluacion_descriptor_id),
    CONSTRAINT fk_descriptor_enlaces_descriptor
        FOREIGN KEY (evaluacion_descriptor_id) REFERENCES evaluacion_descriptores(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_descriptor_enlaces_usuario
        FOREIGN KEY (registrado_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE descriptor_archivo_descargas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descriptor_archivo_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    descargado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_descargas_archivo (descriptor_archivo_id),
    KEY idx_descargas_usuario (user_id),
    CONSTRAINT fk_descargas_archivo
        FOREIGN KEY (descriptor_archivo_id) REFERENCES descriptor_archivos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_descargas_usuario
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =============================================================
-- 5. OBSERVACIONES Y SUBSANACIONES
-- =============================================================

CREATE TABLE observaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluacion_descriptor_id BIGINT UNSIGNED NOT NULL,
    creada_por BIGINT UNSIGNED NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    detalle TEXT NOT NULL,
    estado ENUM('ABIERTA','RESPONDIDA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
    fecha_limite DATE NULL,
    cerrada_por BIGINT UNSIGNED NULL,
    cerrada_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY idx_observaciones_descriptor (evaluacion_descriptor_id),
    KEY idx_observaciones_estado (estado),
    CONSTRAINT fk_observaciones_descriptor
        FOREIGN KEY (evaluacion_descriptor_id) REFERENCES evaluacion_descriptores(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_observaciones_creador
        FOREIGN KEY (creada_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_observaciones_cerrador
        FOREIGN KEY (cerrada_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE observacion_respuestas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    observacion_id BIGINT UNSIGNED NOT NULL,
    respondida_por BIGINT UNSIGNED NOT NULL,
    respuesta TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_respuestas_observacion
        FOREIGN KEY (observacion_id) REFERENCES observaciones(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_respuestas_usuario
        FOREIGN KEY (respondida_por) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =============================================================
-- 6. AUDITORÍA Y NOTIFICACIONES
-- =============================================================

CREATE TABLE auditorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    accion VARCHAR(60) NOT NULL,
    tabla VARCHAR(100) NOT NULL,
    registro_id BIGINT UNSIGNED NULL,
    valores_anteriores JSON NULL,
    valores_nuevos JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_tabla_registro (tabla, registro_id),
    KEY idx_auditoria_usuario (user_id),
    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY idx_notifications_notifiable (notifiable_type, notifiable_id)
) ENGINE=InnoDB;

-- =============================================================
-- 7. VISTAS DE CÁLCULO
-- Regla: los descriptores sin calificar permanecen en el denominador.
-- La categoría final solo existe con 100% de avance.
-- =============================================================

CREATE OR REPLACE VIEW vw_resultados_criterios AS
SELECT
    ed.evaluacion_id,
    c.id AS criterio_id,
    c.dominio_id,
    c.codigo AS criterio_codigo,
    c.nombre AS criterio_nombre,
    COUNT(ed.id) AS total_descriptores,
    SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) AS descriptores_calificados,
    SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) AS descriptores_pendientes,
    COALESCE(SUM(ed.calificacion), 0) AS puntos_obtenidos,
    SUM(de.puntaje_maximo) AS puntos_maximos,
    ROUND(
        COALESCE(SUM(ed.calificacion), 0) /
        NULLIF(SUM(de.puntaje_maximo), 0) * 100,
        2
    ) AS porcentaje_cumplimiento_provisional,
    ROUND(
        SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) /
        NULLIF(COUNT(ed.id), 0) * 100,
        2
    ) AS porcentaje_avance,
    CASE
        WHEN SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) = 0
            THEN 'PENDIENTE'
        WHEN SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) = 0
            THEN 'COMPLETO'
        ELSE 'EN_EVALUACION'
    END AS estado_calculo
FROM evaluacion_descriptores ed
JOIN descriptores de ON de.id = ed.descriptor_id
JOIN criterios c ON c.id = de.criterio_id
GROUP BY ed.evaluacion_id, c.id, c.dominio_id, c.codigo, c.nombre;

CREATE OR REPLACE VIEW vw_resultados_dominios AS
SELECT
    ed.evaluacion_id,
    d.id AS dominio_id,
    d.codigo AS dominio_codigo,
    d.nombre AS dominio_nombre,
    d.peso,
    COUNT(DISTINCT c.id) AS total_criterios,
    COUNT(ed.id) AS total_descriptores,
    SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) AS descriptores_calificados,
    SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) AS descriptores_pendientes,
    COALESCE(SUM(ed.calificacion), 0) AS puntos_obtenidos,
    SUM(de.puntaje_maximo) AS puntos_maximos,
    ROUND(
        COALESCE(SUM(ed.calificacion), 0) /
        NULLIF(SUM(de.puntaje_maximo), 0) * 100,
        2
    ) AS porcentaje_cumplimiento_provisional,
    ROUND(
        SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) /
        NULLIF(COUNT(ed.id), 0) * 100,
        2
    ) AS porcentaje_avance,
    ROUND(
        COALESCE(SUM(ed.calificacion), 0) /
        NULLIF(SUM(de.puntaje_maximo), 0) * d.peso,
        2
    ) AS aporte_ponderado_provisional,
    CASE
        WHEN SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) = 0
            THEN 'PENDIENTE'
        WHEN SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) = 0
            THEN 'COMPLETO'
        ELSE 'EN_EVALUACION'
    END AS estado_calculo
FROM evaluacion_descriptores ed
JOIN descriptores de ON de.id = ed.descriptor_id
JOIN criterios c ON c.id = de.criterio_id
JOIN dominios d ON d.id = c.dominio_id
GROUP BY ed.evaluacion_id, d.id, d.codigo, d.nombre, d.peso;

CREATE OR REPLACE VIEW vw_resultados_generales AS
SELECT
    r.evaluacion_id,
    r.codigo,
    r.nombre,
    r.estado,
    r.dominios_con_resultado,
    r.total_descriptores,
    r.descriptores_calificados,
    r.descriptores_pendientes,
    r.porcentaje_avance,
    r.puntaje_provisional,
    CASE WHEN r.descriptores_pendientes > 0 THEN 'INCOMPLETA' ELSE 'COMPLETA' END
        AS estado_calculo,
    CASE WHEN r.descriptores_pendientes > 0 THEN NULL ELSE cr.nombre END
        AS categoria_final
FROM (
    SELECT
        e.id AS evaluacion_id,
        e.modelo_evaluacion_id,
        e.codigo,
        e.nombre,
        e.estado,
        COUNT(rd.dominio_id) AS dominios_con_resultado,
        SUM(rd.total_descriptores) AS total_descriptores,
        SUM(rd.descriptores_calificados) AS descriptores_calificados,
        SUM(rd.descriptores_pendientes) AS descriptores_pendientes,
        ROUND(
            SUM(rd.descriptores_calificados) /
            NULLIF(SUM(rd.total_descriptores), 0) * 100,
            2
        ) AS porcentaje_avance,
        ROUND(SUM(rd.aporte_ponderado_provisional), 2) AS puntaje_provisional
    FROM evaluaciones e
    JOIN vw_resultados_dominios rd ON rd.evaluacion_id = e.id
    GROUP BY e.id, e.modelo_evaluacion_id, e.codigo, e.nombre, e.estado
) r
LEFT JOIN categorias_resultado cr
  ON cr.modelo_evaluacion_id = r.modelo_evaluacion_id
 AND r.puntaje_provisional BETWEEN cr.porcentaje_desde AND cr.porcentaje_hasta;

-- =============================================================
-- 8. TRIGGERS DE INTEGRIDAD DEL PROCESO
-- =============================================================

DELIMITER $$

CREATE TRIGGER trg_evaluacion_no_cerrar_incompleta
BEFORE UPDATE ON evaluaciones
FOR EACH ROW
BEGIN
    DECLARE v_total_modelo INT DEFAULT 0;
    DECLARE v_total_dominios INT DEFAULT 0;
    DECLARE v_total_instanciados INT DEFAULT 0;
    DECLARE v_pendientes INT DEFAULT 0;
    DECLARE v_autoevaluaciones_enviadas INT DEFAULT 0;

    IF NEW.estado = 'CERRADA' AND OLD.estado <> 'CERRADA' THEN
        SELECT COUNT(*)
          INTO v_total_modelo
          FROM descriptores de
          JOIN criterios c ON c.id = de.criterio_id
          JOIN dominios d ON d.id = c.dominio_id
         WHERE d.modelo_evaluacion_id = NEW.modelo_evaluacion_id
           AND d.activo = TRUE
           AND c.activo = TRUE
           AND de.activo = TRUE;

        SELECT COUNT(*),
               SUM(CASE WHEN calificacion IS NULL THEN 1 ELSE 0 END)
          INTO v_total_instanciados, v_pendientes
          FROM evaluacion_descriptores
         WHERE evaluacion_id = NEW.id;

        SELECT COUNT(*)
          INTO v_total_dominios
          FROM dominios
         WHERE modelo_evaluacion_id = NEW.modelo_evaluacion_id
           AND activo = TRUE;

        SELECT COUNT(*)
          INTO v_autoevaluaciones_enviadas
          FROM autoevaluaciones_dominios ad
          JOIN evaluacion_dominios evd ON evd.id = ad.evaluacion_dominio_id
         WHERE evd.evaluacion_id = NEW.id
           AND ad.estado = 'ENVIADA';

        IF v_total_instanciados <> v_total_modelo
           OR v_pendientes > 0
           OR v_autoevaluaciones_enviadas <> v_total_dominios THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede cerrar: faltan descriptores calificados, ítems del modelo o autoevaluaciones enviadas.';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_no_modificar_calificacion_cerrada
BEFORE UPDATE ON evaluacion_descriptores
FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    DECLARE v_archivos INT DEFAULT 0;

    SELECT estado INTO v_estado
      FROM evaluaciones
     WHERE id = NEW.evaluacion_id;

    IF v_estado = 'CERRADA' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede modificar una calificación de una evaluación cerrada.';
    END IF;

    IF NEW.calificacion IS NOT NULL THEN
        SELECT COUNT(*) INTO v_archivos
          FROM descriptor_archivos
         WHERE evaluacion_descriptor_id = NEW.id
           AND deleted_at IS NULL;

        IF v_archivos = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede calificar un descriptor sin al menos un archivo de evidencia.';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_no_cargar_archivo_evaluacion_cerrada
BEFORE INSERT ON descriptor_archivos
FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);

    SELECT e.estado INTO v_estado
      FROM evaluacion_descriptores ed
      JOIN evaluaciones e ON e.id = ed.evaluacion_id
     WHERE ed.id = NEW.evaluacion_descriptor_id;

    IF v_estado IN ('CERRADA','CANCELADA') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se pueden cargar archivos en una evaluación cerrada o cancelada.';
    END IF;
END$$

CREATE TRIGGER trg_no_eliminar_archivo_calificado
BEFORE UPDATE ON descriptor_archivos
FOR EACH ROW
BEGIN
    DECLARE v_calificacion TINYINT UNSIGNED;
    DECLARE v_estado VARCHAR(30);

    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        SELECT ed.calificacion, e.estado INTO v_calificacion, v_estado
          FROM evaluacion_descriptores ed
          JOIN evaluaciones e ON e.id = ed.evaluacion_id
         WHERE ed.id = NEW.evaluacion_descriptor_id;

        IF v_calificacion IS NOT NULL OR v_estado IN ('CERRADA','CANCELADA') THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede eliminar evidencia de un descriptor calificado o de una evaluación cerrada.';
        END IF;
    END IF;
END$$

DELIMITER ;

-- =============================================================
-- 9. DATOS SEMILLA: ROLES Y MODELO OFICIAL DE LA FICHA
-- =============================================================

INSERT INTO roles (id, codigo, nombre, descripcion, created_at, updated_at) VALUES
(1, 'ADMINISTRADOR', 'Administrador general', 'Administración completa del sistema.', NOW(), NOW()),
(2, 'RESPONSABLE_DOMINIO', 'Responsable de dominio', 'Administra evidencias únicamente del dominio asignado.', NOW(), NOW()),
(3, 'EVALUADOR_EXTERNO', 'Evaluador externo', 'Revisa evidencias y asigna calificaciones 0, 1 o 2.', NOW(), NOW()),
(4, 'AUDITOR_LECTURA', 'Auditor de lectura', 'Consulta resultados, evidencias e historial sin modificar información.', NOW(), NOW());

INSERT INTO modelos_evaluacion
(id, nombre, version, descripcion, estado, publicado_at, created_at, updated_at)
VALUES
(1, 'Modelo de Evaluación de la Calidad del Hospital de Simulación', '1.0',
 'Modelo basado en la ficha de evaluación suministrada para el proyecto.',
 'PUBLICADO', NOW(), NOW(), NOW());

INSERT INTO dominios
(id, modelo_evaluacion_id, codigo, nombre, peso, orden, activo, created_at, updated_at)
VALUES
(1, 1, 'D1', 'Aspectos académicos', 25.00, 1, TRUE, NOW(), NOW()),
(2, 1, 'D2', 'Mejoramiento continuo', 15.00, 2, TRUE, NOW(), NOW()),
(3, 1, 'D3', 'Perfil docente', 25.00, 3, TRUE, NOW(), NOW()),
(4, 1, 'D4', 'Personal asignado al centro', 15.00, 4, TRUE, NOW(), NOW()),
(5, 1, 'D5', 'Organización administrativa, infraestructura, equipamiento y proyección social', 20.00, 5, TRUE, NOW(), NOW());

INSERT INTO criterios
(id, dominio_id, codigo, nombre, orden, activo, created_at, updated_at)
VALUES
-- Ante diferencias de nombres entre el instructivo y la ficha, estos criterios
-- siguen la FICHA DE EVALUACIÓN, que es el instrumento operativo calificable.
(1, 1, '1', 'Objetivos de aprendizaje', 1, TRUE, NOW(), NOW()),
(2, 1, '2', 'Modelo de simulación y fidelidad', 2, TRUE, NOW(), NOW()),
(3, 1, '3', 'Prebriefing', 3, TRUE, NOW(), NOW()),
(4, 1, '4', 'Debriefing', 4, TRUE, NOW(), NOW()),
(5, 1, '5', 'Evaluación', 5, TRUE, NOW(), NOW()),
(6, 2, '1', 'Percepción de la comunidad sobre los procesos administrativos', 1, TRUE, NOW(), NOW()),
(7, 3, '1', 'Desarrollo docente', 1, TRUE, NOW(), NOW()),
(8, 3, '2', 'Actividades derivadas de la docencia', 2, TRUE, NOW(), NOW()),
(9, 4, '1', 'Administrativo', 1, TRUE, NOW(), NOW()),
(10, 4, '2', 'Mantenimiento y seguridad', 2, TRUE, NOW(), NOW()),
(11, 4, '3', 'Recursos físicos', 3, TRUE, NOW(), NOW()),
(12, 5, '1', 'Dirección y cargos', 1, TRUE, NOW(), NOW()),
(13, 5, '2', 'Mantenimiento y seguridad', 2, TRUE, NOW(), NOW()),
(14, 5, '3', 'Recursos físicos', 3, TRUE, NOW(), NOW()),
(15, 5, '4', 'Capacidad instalada', 4, TRUE, NOW(), NOW()),
(16, 5, '5', 'Complejidad del centro', 5, TRUE, NOW(), NOW()),
(17, 5, '6', 'Proyección social', 6, TRUE, NOW(), NOW());

INSERT INTO descriptores
(id, criterio_id, codigo, descripcion, orden, puntaje_maximo, activo, created_at, updated_at)
VALUES
-- Dominio 1 / Objetivos de aprendizaje
(1, 1, '1.1', 'Los objetivos, competencias o resultados de aprendizaje son medibles y coherentes con las actividades de simulación (orientados por una Taxonomía como Bloom Modificada o SMART (Specific, Measurable, Assignable, Realistic, Time Related) entre otras.', 1, 2, TRUE, NOW(), NOW()),
(2, 1, '1.2', 'Los materiales de estudio previos son coherentes con los objetivos de aprendizaje.', 2, 2, TRUE, NOW(), NOW()),
(3, 1, '1.3', 'Los elementos no pertinentes al objetivo del escenario de simulación son previamente identificados y gestionados.', 3, 2, TRUE, NOW(), NOW()),

-- Dominio 1 / Modelo de simulación y fidelidad
(4, 2, '2.1', 'Las características del modelo de simulación están claramente definidas (paciente estandarizado, simulador, caso clínico o procedimiento).', 1, 2, TRUE, NOW(), NOW()),
(5, 2, '2.2', 'La naturaleza del escenario o actividad están alineadas con las necesidades locales formativas, regulaciones institucionales y locales vigentes.', 2, 2, TRUE, NOW(), NOW()),
(6, 2, '2.3', 'Existe una ruta estandarizada para la solicitud, gestión y planeación de la actividad y el uso de los modelos de simulación.', 3, 2, TRUE, NOW(), NOW()),
(7, 2, '2.4', 'Existe un formato de guion estructurado para la planeación de la actividad.', 4, 2, TRUE, NOW(), NOW()),
(8, 2, '2.5', 'La dinámica del escenario está acorde con el grado de fidelidad propuesto (Fidelidad Psicológica).', 5, 2, TRUE, NOW(), NOW()),
(9, 2, '2.6', 'El contexto físico de la actividad basada en simulación replica el entorno real, de acuerdo con el nivel de complejidad (Fidelidad Física).', 6, 2, TRUE, NOW(), NOW()),
(10, 2, '2.7', 'Los elementos del escenario están relacionados con las necesidades del caso (Fidelidad Conceptual).', 7, 2, TRUE, NOW(), NOW()),
(11, 2, '2.8', 'Se cuenta con los equipos o simuladores mínimos requeridos para el caso o procedimiento propuesto.', 8, 2, TRUE, NOW(), NOW()),
(12, 2, '2.9', 'La complejidad del escenario coincide con el nivel académico de los estudiantes.', 9, 2, TRUE, NOW(), NOW()),

-- Dominio 1 / Prebriefing
(13, 3, '3.1', 'Se proporciona material de estudio con antelación por parte del docente, para la preparación del ejercicio de simulación por parte del estudiante.', 1, 2, TRUE, NOW(), NOW()),
(14, 3, '3.2', 'Los elementos del prebriefing (seguridad psicológica, acuerdos de confidencialidad, respeto y ficción, objetivos del escenario y expectativas) son abordados en la sesión.', 2, 2, TRUE, NOW(), NOW()),
(15, 3, '3.3', 'El tiempo invertido para el prebriefing es suficiente para informar a los estudiantes sobre los elementos necesarios, aclarar dudas, explorar emociones y establecer las bases del escenario (fortalezas, limitaciones, roles, etc.).', 3, 2, TRUE, NOW(), NOW()),

-- Dominio 1 / Debriefing
(16, 4, '4.1', 'Se usa el debriefing como espacio de reflexión y diálogo académico para alcanzar los objetivos de la sesión de simulación.', 1, 2, TRUE, NOW(), NOW()),
(17, 4, '4.2', 'El instructor cuenta con habilidades básicas para llevar a cabo el debriefing de manera adecuada (establece y mantiene un ambiente de aprendizaje participativo, aplica con estructura el debriefing, genera discusiones estimulantes y profundas, identifica y explora brechas de conocimiento/productividad, ayuda a lograr o mantener un buen rendimiento).', 2, 2, TRUE, NOW(), NOW()),
(18, 4, '4.3', 'Existe un espacio físico destinado para el desarrollo del debriefing.', 3, 2, TRUE, NOW(), NOW()),
(19, 4, '4.4', 'El tiempo para el debriefing es el doble del tiempo del prebriefing.', 4, 2, TRUE, NOW(), NOW()),

-- Dominio 1 / Evaluación
(20, 5, '5.1', 'El centro cuenta con sistemas de evaluación (medible, observable, con sistema de calificación claro) para el aprendizaje y del aprendizaje.', 1, 2, TRUE, NOW(), NOW()),
(21, 5, '5.2', 'Los sistemas de evaluación están alineados con los resultados de aprendizaje.', 2, 2, TRUE, NOW(), NOW()),
(22, 5, '5.3', 'Utiliza herramientas de evaluación estandarizadas.', 3, 2, TRUE, NOW(), NOW()),
(23, 5, '5.4', 'Los sistemas de evaluación están alineados con la estrategia didáctica.', 4, 2, TRUE, NOW(), NOW()),

-- Dominio 2
(24, 6, '1.1', 'Apreciación de estudiantes y profesores sobre la eficacia de las estrategias, el plan de trabajo y actividades docentes que involucran el centro de simulación clínica con las actividades académicas.', 1, 2, TRUE, NOW(), NOW()),
(25, 6, '1.2', 'Procesos de actualización con visitas de referenciación.', 2, 2, TRUE, NOW(), NOW()),

-- Dominio 3
(26, 7, '1.1', 'Los docentes tienen formación y/o experiencia en simulación clínica.', 1, 2, TRUE, NOW(), NOW()),
(27, 7, '1.2', 'Existen actividades de capacitación y desarrollo profesoral organizadas desde la institución, para los docentes vinculados a las prácticas en simulación clínica (actualización bianual - 2 años).', 2, 2, TRUE, NOW(), NOW()),
(28, 8, '2.1', 'Evidencia de la participación de profesores en la actualización y elaboración de las guías de simulación clínica y de las actividades académicas relacionadas.', 1, 2, TRUE, NOW(), NOW()),

-- Dominio 4
(29, 9, '1.1', 'El centro cuenta con un coordinador dedicado a las actividades administrativas del mismo.', 1, 2, TRUE, NOW(), NOW()),
(30, 10, '2.1', 'El centro cuenta uno o más operarios y/o técnicos, encargado de las actividades diarias del mismo y mantenimiento general de los equipos.', 1, 2, TRUE, NOW(), NOW()),
(31, 11, '3.1', 'El centro cuenta con personal con formación en actuación para cumplir las funciones de paciente simulado.', 1, 2, TRUE, NOW(), NOW()),

-- Dominio 5 / Dirección y cargos
(32, 12, '1.1', 'Demostrar la existencia de una organización que dirige los aspectos administrativos, financieros y académicos del centro de simulación.', 1, 2, TRUE, NOW(), NOW()),
(33, 12, '1.2', 'Existencia de las funciones del talento humano asignado al centro de simulación clínica (personal operativo, administrativo, educativo).', 2, 2, TRUE, NOW(), NOW()),

-- Dominio 5 / Mantenimiento y seguridad
(34, 13, '2.1', 'Existencia de actividades que incluyen el mantenimiento de infraestructura y equipos en el centro de simulación.', 1, 2, TRUE, NOW(), NOW()),
(35, 13, '2.2', 'Existencia de manuales requeridos para el funcionamiento de los simuladores existentes y la demostración de capacitación de buenas prácticas para estudiantes, docentes y la comunidad que utiliza el centro de simulación.', 2, 2, TRUE, NOW(), NOW()),
(36, 13, '2.3', 'Cumplimiento de las normas de bioseguridad requeridas en el centro de simulación.', 3, 2, TRUE, NOW(), NOW()),
(37, 13, '2.4', 'Proyecto de autosostenibilidad financiera del centro.', 4, 2, TRUE, NOW(), NOW()),

-- Dominio 5 / Recursos físicos
(38, 14, '3.1', 'Demostración de los recursos físicos utilizados en el centro de simulación clínica con relación al número de prácticas, estudiantes, programas y necesidades del contexto (Ver lista de chequeo elementos básicos).', 1, 2, TRUE, NOW(), NOW()),
(39, 14, '3.2', 'Espacios físicos flexibles más que extensos, y dinámicos para adecuar según las necesidades de los escenarios (con enfoque multipropósito).', 2, 2, TRUE, NOW(), NOW()),
(40, 14, '3.3', 'Lugares con tecnología de grabación (sistema audiovisual).', 3, 2, TRUE, NOW(), NOW()),
(41, 14, '3.4', 'Salas de observación alternas a los escenarios (sala de espejo y sala de control).', 4, 2, TRUE, NOW(), NOW()),

-- Dominio 5 / Capacidad, complejidad y proyección social
(42, 15, '4.1', 'Está establecido el análisis de capacidad instalada del centro.', 1, 2, TRUE, NOW(), NOW()),
(43, 16, '5.1', 'Se identifica y se declara el nivel de complejidad del centro de simulación de acuerdo con SIMZONES.', 1, 2, TRUE, NOW(), NOW()),
(44, 17, '6.1', 'Existencia de proyectos sin ánimo de lucro, que aporten a la comunidad general y sociedad.', 1, 2, TRUE, NOW(), NOW());

INSERT INTO categorias_resultado
(id, modelo_evaluacion_id, nombre, porcentaje_desde, porcentaje_hasta, interpretacion, orden, created_at, updated_at)
VALUES
(1, 1, 'Centro Emergente', 0.00, 39.99, 'Cumple pocos criterios de calidad. Se encuentra en etapa inicial de desarrollo.', 1, NOW(), NOW()),
(2, 1, 'Centro Consolidado', 40.00, 59.99, 'Cumple criterios básicos y operativos; funcional, pero con áreas clave por mejorar.', 2, NOW(), NOW()),
(3, 1, 'Centro de alta calidad', 60.00, 79.99, 'Alto cumplimiento de estándares; desempeño sólido y consistente.', 3, NOW(), NOW()),
(4, 1, 'Centro de referencia', 80.00, 100.00, 'Excelencia en la mayoría de los criterios; modelo para otros centros.', 4, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- 10. INICIALIZACIÓN DE UNA EVALUACIÓN (REFERENCIA PARA LARAVEL)
-- =============================================================
-- Después de crear la fila en evaluaciones, Laravel debe ejecutar en una transacción:
--
-- INSERT INTO evaluacion_dominios (evaluacion_id, dominio_id, responsable_id, estado, created_at, updated_at)
-- SELECT :evaluacion_id, d.id,
--        CASE d.id
--            WHEN 1 THEN :responsable_d1
--            WHEN 2 THEN :responsable_d2
--            WHEN 3 THEN :responsable_d3
--            WHEN 4 THEN :responsable_d4
--            WHEN 5 THEN :responsable_d5
--        END,
--        'PENDIENTE', NOW(), NOW()
-- FROM dominios d
-- JOIN evaluaciones e ON e.id = :evaluacion_id
-- WHERE d.modelo_evaluacion_id = e.modelo_evaluacion_id
--   AND d.activo = TRUE;
--
-- INSERT INTO evaluacion_descriptores
--     (evaluacion_id, descriptor_id, estado, calificacion, created_at, updated_at)
-- SELECT :evaluacion_id, de.id, 'PENDIENTE', NULL, NOW(), NOW()
-- FROM descriptores de
-- JOIN criterios c ON c.id = de.criterio_id
-- JOIN dominios d ON d.id = c.dominio_id
-- JOIN evaluaciones e ON e.id = :evaluacion_id
-- WHERE d.modelo_evaluacion_id = e.modelo_evaluacion_id
--   AND d.activo = TRUE
--   AND c.activo = TRUE
--   AND de.activo = TRUE;
--
-- Para el modelo semilla se crearán 5 dominios de proceso y 44 evidencias evaluables.
