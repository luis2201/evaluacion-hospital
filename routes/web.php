<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Evaluation\AutoevaluationController;
use App\Http\Controllers\Evaluation\DescriptorEvidenceController;
use App\Http\Controllers\Evaluation\DescriptorReviewController;
use App\Http\Controllers\Evaluation\EvaluationController;
use App\Http\Controllers\Evaluation\EvaluationResultController;
use App\Http\Controllers\Evaluation\EvaluationWorkflowController;
use App\Http\Controllers\Instrument\CriterionController;
use App\Http\Controllers\Instrument\DescriptorController;
use App\Http\Controllers\Instrument\DomainController;
use App\Http\Controllers\Instrument\InstrumentController;
use App\Http\Controllers\Instrument\InstrumentStateController;
use App\Http\Controllers\Instrument\ResultCategoryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Setup\InitialAdministratorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('configuracion-inicial', [InitialAdministratorController::class, 'create'])->name('setup.create');
    Route::post('configuracion-inicial', [InitialAdministratorController::class, 'store'])->middleware('throttle:5,1')->name('setup.store');
});

Route::middleware(['guest', 'initialized'])->group(function (): void {
    Route::get('iniciar-sesion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('iniciar-sesion', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('recuperar-contrasena', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('recuperar-contrasena', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('restablecer-contrasena/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('restablecer-contrasena', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('cerrar-sesion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('perfil/contrasena', [PasswordController::class, 'edit'])->name('profile.password.edit');
    Route::put('perfil/contrasena', [PasswordController::class, 'update'])->name('profile.password.update');
    Route::get('configuracion', [SettingsController::class, 'show'])->name('settings.show');
    Route::get('instrumentos', [InstrumentController::class, 'index'])->name('instruments.index');
    Route::get('instrumentos/{instrumento}', [InstrumentController::class, 'show'])->name('instruments.show');
    Route::get('evaluaciones', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('evaluaciones/{evaluacion}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::get('evaluaciones/{evaluacion}/resultados', [EvaluationResultController::class, 'show'])->name('evaluations.results');
    Route::post('evaluaciones/{evaluacion}/dominios/{evaluacionDominio}/autoevaluacion', [AutoevaluationController::class, 'store'])->name('evaluations.domains.autoevaluation.store');
    Route::post('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/archivos', [DescriptorEvidenceController::class, 'store'])->name('evaluations.descriptors.files.store');
    Route::get('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/archivos/{descriptorArchivo}/visualizar', [DescriptorEvidenceController::class, 'preview'])->name('evaluations.descriptors.files.preview');
    Route::get('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/archivos/{descriptorArchivo}/descargar', [DescriptorEvidenceController::class, 'download'])->name('evaluations.descriptors.files.download');
    Route::delete('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/archivos/{descriptorArchivo}', [DescriptorEvidenceController::class, 'destroy'])->name('evaluations.descriptors.files.destroy');
    Route::post('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/enlaces', [DescriptorEvidenceController::class, 'storeLink'])->name('evaluations.descriptors.links.store');
    Route::delete('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/enlaces/{descriptorEnlace}', [DescriptorEvidenceController::class, 'destroyLink'])->name('evaluations.descriptors.links.destroy');
    Route::post('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/calificar', [DescriptorReviewController::class, 'grade'])->name('evaluations.descriptors.review.grade');
    Route::post('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/observaciones', [DescriptorReviewController::class, 'observe'])->name('evaluations.descriptors.observations.store');
    Route::post('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/observaciones/{observacion}/responder', [DescriptorReviewController::class, 'respond'])->name('evaluations.descriptors.observations.respond');
    Route::post('evaluaciones/{evaluacion}/descriptores/{evaluacionDescriptor}/observaciones/{observacion}/cerrar', [DescriptorReviewController::class, 'close'])->name('evaluations.descriptors.observations.close');

    Route::prefix('administracion')->name('admin.')->middleware('role:ADMINISTRADOR')->group(function (): void {
        Route::put('configuracion', [SettingsController::class, 'update'])->name('settings.update');
        Route::resource('usuarios', UserController::class)->parameters(['usuarios' => 'user'])->except(['show', 'destroy'])->names('users');
        Route::delete('usuarios/{user}/sesiones', [UserSessionController::class, 'destroy'])->name('users.sessions.destroy');
        Route::get('evaluaciones/nueva', [EvaluationController::class, 'create'])->name('evaluations.create');
        Route::post('evaluaciones', [EvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('evaluaciones/{evaluacion}/editar', [EvaluationController::class, 'edit'])->name('evaluations.edit');
        Route::put('evaluaciones/{evaluacion}', [EvaluationController::class, 'update'])->name('evaluations.update');
        Route::put('evaluaciones/{evaluacion}/cronograma', [EvaluationController::class, 'updateSchedule'])->name('evaluations.schedule.update');
        Route::post('evaluaciones/{evaluacion}/habilitar-carga', [EvaluationWorkflowController::class, 'start'])->name('evaluations.start');
        Route::post('evaluaciones/{evaluacion}/iniciar-revision', [EvaluationWorkflowController::class, 'startReview'])->name('evaluations.review.start');
        Route::post('evaluaciones/{evaluacion}/cancelar', [EvaluationWorkflowController::class, 'cancel'])->name('evaluations.cancel');
        Route::post('evaluaciones/{evaluacion}/cerrar', [EvaluationResultController::class, 'close'])->name('evaluations.close');
        Route::get('instrumentos/nuevo', [InstrumentController::class, 'create'])->name('instruments.create');
        Route::post('instrumentos', [InstrumentController::class, 'store'])->name('instruments.store');
        Route::get('instrumentos/{instrumento}/editar', [InstrumentController::class, 'edit'])->name('instruments.edit');
        Route::put('instrumentos/{instrumento}', [InstrumentController::class, 'update'])->name('instruments.update');
        Route::post('instrumentos/{instrumento}/duplicar', [InstrumentStateController::class, 'duplicate'])->name('instruments.duplicate');
        Route::post('instrumentos/{instrumento}/publicar', [InstrumentStateController::class, 'publish'])->name('instruments.publish');
        Route::post('instrumentos/{instrumento}/archivar', [InstrumentStateController::class, 'archive'])->name('instruments.archive');
        Route::post('instrumentos/{instrumento}/dominios', [DomainController::class, 'store'])->name('instruments.domains.store');
        Route::put('instrumentos/{instrumento}/dominios/{dominio}', [DomainController::class, 'update'])->name('instruments.domains.update');
        Route::delete('instrumentos/{instrumento}/dominios/{dominio}', [DomainController::class, 'destroy'])->name('instruments.domains.destroy');
        Route::post('instrumentos/{instrumento}/dominios/{dominio}/criterios', [CriterionController::class, 'store'])->name('instruments.criteria.store');
        Route::put('instrumentos/{instrumento}/dominios/{dominio}/criterios/{criterio}', [CriterionController::class, 'update'])->name('instruments.criteria.update');
        Route::delete('instrumentos/{instrumento}/dominios/{dominio}/criterios/{criterio}', [CriterionController::class, 'destroy'])->name('instruments.criteria.destroy');
        Route::post('instrumentos/{instrumento}/criterios/{criterio}/descriptores', [DescriptorController::class, 'store'])->name('instruments.descriptors.store');
        Route::put('instrumentos/{instrumento}/criterios/{criterio}/descriptores/{descriptor}', [DescriptorController::class, 'update'])->name('instruments.descriptors.update');
        Route::delete('instrumentos/{instrumento}/criterios/{criterio}/descriptores/{descriptor}', [DescriptorController::class, 'destroy'])->name('instruments.descriptors.destroy');
        Route::post('instrumentos/{instrumento}/categorias', [ResultCategoryController::class, 'store'])->name('instruments.categories.store');
        Route::put('instrumentos/{instrumento}/categorias/{categoria}', [ResultCategoryController::class, 'update'])->name('instruments.categories.update');
        Route::delete('instrumentos/{instrumento}/categorias/{categoria}', [ResultCategoryController::class, 'destroy'])->name('instruments.categories.destroy');
    });
});
