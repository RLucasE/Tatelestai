<script setup>
import { onMounted, onBeforeUnmount, shallowRef, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const route = useRoute();
const router = useRouter();
const establishmentId = route.params.id;

const establishment = ref(null);
const loading = shallowRef(true);
const error = shallowRef(null);
const verifying = shallowRef(false);
const rejecting = shallowRef(false);
const rejectReason = shallowRef('');
const showRejectModal = shallowRef(false);

// Revisión de archivos individuales
const showFileRejectModal = shallowRef(false);
const selectedFileForReject = ref(null);
const fileRejectReason = shallowRef('');

// Validación Google Places y llamada
const googleVerifying = shallowRef(false);
const phoneVerifying = shallowRef(false);
const showPhoneModal = shallowRef(false);
const phoneNotes = shallowRef('');

// Guardado en bloque
const savingReviews = shallowRef(false);
const saveSuccessMessage = shallowRef('');

// Vista previa rápida embebida
const selectedPreviewFile = ref(null);
const previewBlobUrl = shallowRef(null);
const previewLoading = shallowRef(false);
const previewError = shallowRef('');
const openingFileId = shallowRef(null);

const isImageFile = (fileType = '') => ['jpg', 'jpeg', 'png', 'webp'].includes(String(fileType).toLowerCase());
const isPdfFile = (fileType = '') => String(fileType).toLowerCase() === 'pdf';

const getFileTypeLabel = (fileType) => {
  if (isImageFile(fileType)) return 'Imagen';
  if (isPdfFile(fileType)) return 'PDF';
  return 'Documento';
};

const documentCategoryOptions = [
  { value: 'tax_certificate', label: 'Constancia CUIT (ARCA / AFIP)', points: 30 },
  { value: 'municipal_permit', label: 'Habilitación Municipal u Oblea (ARMSa)', points: 30 },
  { value: 'municipal_tax_receipt', label: 'Boleta de Tasa Municipal (TISSH)', points: 30 },
  { value: 'pest_control_certificate', label: 'Certificado de Fumigación y Desinfección', points: 20 },
  { value: 'facade_photo', label: 'Foto de Fachada Exterior', points: 10 },
  { value: 'store_counter_photo', label: 'Foto de Mostrador / Cocina', points: 10 },
  { value: 'other', label: 'Otro documento adicional', points: 10 },
];

const getDocumentCategoryLabel = (cat) => {
  return documentCategoryOptions.find((o) => o.value === cat)?.label || 'Documento adjunto';
};

const getDocumentMaxPoints = (file) => {
  if (file.edit_category === 'other') return Number(file.edit_manual_points) || 0;
  return documentCategoryOptions.find((o) => o.value === file.edit_category)?.points ?? 10;
};

const getFileDisplayName = (file) => {
  if (file.original_name) return file.original_name;
  const path = file.file_path || '';
  const base = path.split('/').pop();
  return base || `documento_${file.id}.${file.file_type || 'pdf'}`;
};

const isGooglePlaceVerified = computed(() => {
  return establishment.value?.trust_logs?.some(
    (log) => log.source_type === 'google_places_profile' && log.is_active
  );
});

const isPhoneCallVerified = computed(() => {
  return establishment.value?.trust_logs?.some(
    (log) => log.source_type === 'phone_call_verification' && log.is_active
  );
});

const initializeFilesState = () => {
  if (!establishment.value?.verification_files) return;
  establishment.value.verification_files.forEach((file) => {
    const rawCategory = file.document_category?.value || file.document_category || 'other';
    file.edit_status = file.status || 'pending';
    file.edit_category = rawCategory;
    file.edit_rejection_reason = file.rejection_reason || '';
    file.edit_manual_points = typeof file.assigned_points === 'number' ? file.assigned_points : 10;

    file.original_status = file.status || 'pending';
    file.original_category = rawCategory;
    file.original_rejection_reason = file.rejection_reason || '';
    file.original_manual_points = file.edit_manual_points;
  });

  if (establishment.value.verification_files.length > 0 && !selectedPreviewFile.value) {
    loadPreview(establishment.value.verification_files[0]);
  }
};

const hasUnsavedChanges = computed(() => {
  const files = establishment.value?.verification_files || [];
  return files.some((file) =>
    file.edit_status !== file.original_status ||
    file.edit_category !== file.original_category ||
    file.edit_rejection_reason !== file.original_rejection_reason ||
    (file.edit_category === 'other' && file.edit_status === 'accepted' && file.edit_manual_points !== file.original_manual_points)
  );
});

const verifiedFilesCount = computed(() => {
  return (establishment.value?.verification_files || []).filter((f) => f.edit_status === 'accepted').length;
});

const totalFilesCount = computed(() => {
  return establishment.value?.verification_files?.length || 0;
});

const hasGooglePlace = computed(() => {
  return Boolean(
    establishment.value?.google_place_id ||
    (establishment.value?.google_place_data && Object.keys(establishment.value.google_place_data).length > 0)
  );
});

const availablePhoneNumber = computed(() => {
  return (
    establishment.value?.google_place_data?.formatted_phone_number ||
    establishment.value?.google_place_data?.international_phone_number ||
    establishment.value?.phone ||
    null
  );
});

const googleMapsUrl = computed(() => {
  const lat = establishment.value?.latitude;
  const lng = establishment.value?.longitude;
  if (lat && lng) {
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
  }
  if (establishment.value?.address) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(establishment.value.address)}`;
  }
  return null;
});

const trustScoreBreakdown = computed(() => {
  const files = establishment.value?.verification_files || [];

  const isDocAccepted = (cat) => files.some((f) => f.edit_category === cat && f.edit_status === 'accepted');
  const isDocRejected = (cat) => files.some((f) => f.edit_category === cat && f.edit_status === 'rejected');
  const hasDocUploaded = (cat) => files.some((f) => f.edit_category === cat);

  const standardList = [
    {
      id: 'tax_certificate',
      name: 'Constancia CUIT (ARCA / AFIP)',
      maxPoints: 30,
      isVerified: isDocAccepted('tax_certificate'),
      isRejected: isDocRejected('tax_certificate'),
      isUploaded: hasDocUploaded('tax_certificate'),
      earnedPoints: isDocAccepted('tax_certificate') ? 30 : 0,
    },
    {
      id: 'municipal_permit',
      name: 'Habilitación Municipal u Oblea (ARMSa)',
      maxPoints: 30,
      isVerified: isDocAccepted('municipal_permit'),
      isRejected: isDocRejected('municipal_permit'),
      isUploaded: hasDocUploaded('municipal_permit'),
      earnedPoints: isDocAccepted('municipal_permit') ? 30 : 0,
    },
    {
      id: 'municipal_tax_receipt',
      name: 'Boleta de Tasa Municipal (TISSH)',
      maxPoints: 30,
      isVerified: isDocAccepted('municipal_tax_receipt'),
      isRejected: isDocRejected('municipal_tax_receipt'),
      isUploaded: hasDocUploaded('municipal_tax_receipt'),
      earnedPoints: isDocAccepted('municipal_tax_receipt') ? 30 : 0,
    },
    {
      id: 'pest_control_certificate',
      name: 'Certificado de Fumigación',
      maxPoints: 20,
      isVerified: isDocAccepted('pest_control_certificate'),
      isRejected: isDocRejected('pest_control_certificate'),
      isUploaded: hasDocUploaded('pest_control_certificate'),
      earnedPoints: isDocAccepted('pest_control_certificate') ? 20 : 0,
    },
    {
      id: 'facade_photo',
      name: 'Foto de Fachada Exterior',
      maxPoints: 10,
      isVerified: isDocAccepted('facade_photo'),
      isRejected: isDocRejected('facade_photo'),
      isUploaded: hasDocUploaded('facade_photo'),
      earnedPoints: isDocAccepted('facade_photo') ? 10 : 0,
    },
    {
      id: 'store_counter_photo',
      name: 'Foto de Mostrador / Cocina',
      maxPoints: 10,
      isVerified: isDocAccepted('store_counter_photo'),
      isRejected: isDocRejected('store_counter_photo'),
      isUploaded: hasDocUploaded('store_counter_photo'),
      earnedPoints: isDocAccepted('store_counter_photo') ? 10 : 0,
    },
    {
      id: 'google_places_profile',
      name: 'Ficha comercial en Google Maps',
      maxPoints: 20,
      isVerified: isGooglePlaceVerified.value,
      isRejected: false,
      isUploaded: hasGooglePlace.value,
      earnedPoints: isGooglePlaceVerified.value ? 20 : 0,
    },
    {
      id: 'phone_call_verification',
      name: 'Llamada telefónica confirmada',
      maxPoints: 30,
      isVerified: isPhoneCallVerified.value,
      isRejected: false,
      isUploaded: Boolean(availablePhoneNumber.value),
      earnedPoints: isPhoneCallVerified.value ? 30 : 0,
    },
  ];

  const otherList = files
    .filter((f) => f.edit_category === 'other')
    .map((f, idx) => {
      const isAccepted = f.edit_status === 'accepted';
      const isRejected = f.edit_status === 'rejected';
      const customPts = isAccepted ? (Number(f.edit_manual_points) || 0) : 0;
      return {
        id: `other_${f.id}`,
        name: `Otro documento #${idx + 1} (${getFileTypeLabel(f.file_type)})`,
        maxPoints: Number(f.edit_manual_points) || 10,
        isVerified: isAccepted,
        isRejected,
        isUploaded: true,
        earnedPoints: customPts,
      };
    });

  return [...standardList, ...otherList];
});

const totalCalculatedPoints = computed(() => {
  return trustScoreBreakdown.value.reduce((acc, item) => acc + item.earnedPoints, 0);
});

const totalPossiblePoints = computed(() => {
  return Math.max(150, trustScoreBreakdown.value.reduce((acc, item) => acc + item.maxPoints, 0));
});

const calculatedTrustLevel = computed(() => {
  const pts = totalCalculatedPoints.value;
  if (pts >= 70) return 'green';
  if (pts >= 40) return 'yellow';
  return 'red';
});

const trustProgressPercentage = computed(() => {
  return Math.min(100, Math.round((totalCalculatedPoints.value / totalPossiblePoints.value) * 100));
});

const revertLocalChanges = () => {
  if (!establishment.value?.verification_files) return;
  establishment.value.verification_files.forEach((file) => {
    file.edit_status = file.original_status;
    file.edit_category = file.original_category;
    file.edit_rejection_reason = file.original_rejection_reason;
    file.edit_manual_points = file.original_manual_points;
  });
};

const fetchEstablishment = async (isInitial = true) => {
  try {
    if (isInitial) loading.value = true;
    const response = await axiosInstance.get(`/adm/pending-establishments/${establishmentId}`);
    establishment.value = response.data.data || null;
    initializeFilesState();
    error.value = null;
  } catch (err) {
    console.error('Error fetching establishment detail:', err);
    if (isInitial) {
      error.value = 'No se pudo cargar la información del establecimiento.';
      establishment.value = null;
    }
  } finally {
    if (isInitial) loading.value = false;
  }
};

const toggleVerifyLocal = (file) => {
  if (file.edit_status === 'accepted') {
    file.edit_status = 'pending';
  } else {
    file.edit_status = 'accepted';
    file.edit_rejection_reason = '';
  }
};

const openFileRejectModal = (file) => {
  selectedFileForReject.value = file;
  fileRejectReason.value = file.edit_rejection_reason || '';
  showFileRejectModal.value = true;
};

const closeFileRejectModal = () => {
  selectedFileForReject.value = null;
  fileRejectReason.value = '';
  showFileRejectModal.value = false;
};

const confirmRejectFile = () => {
  if (!selectedFileForReject.value) return;
  selectedFileForReject.value.edit_status = 'rejected';
  selectedFileForReject.value.edit_rejection_reason = fileRejectReason.value.trim() || 'Documento no válido o ilegible';
  closeFileRejectModal();
};

const changeManualPoints = (file, delta) => {
  const current = Number(file.edit_manual_points || 0);
  file.edit_manual_points = Math.min(50, Math.max(0, current + delta));
};

const sanitizeManualPoints = (file) => {
  let val = Number(file.edit_manual_points);
  if (Number.isNaN(val) || val < 0) val = 0;
  if (val > 50) val = 50;
  file.edit_manual_points = val;
};

const saveDocumentReviews = async () => {
  if (!establishment.value || savingReviews.value) return;

  savingReviews.value = true;
  saveSuccessMessage.value = '';

  try {
    const payload = {
      files: establishment.value.verification_files.map((file) => ({
        id: file.id,
        status: file.edit_status,
        document_category: file.edit_category,
        rejection_reason: file.edit_status === 'rejected' ? file.edit_rejection_reason : null,
        points: file.edit_category === 'other' ? (Number(file.edit_manual_points) || 0) : null,
      })),
    };

    const res = await axiosInstance.post(
      `/adm/establishments/${establishment.value.id}/review-documents`,
      payload
    );

    establishment.value.trust_score = res.data.trust_score;
    establishment.value.trust_level = res.data.trust_level;

    establishment.value.verification_files.forEach((file) => {
      file.status = file.edit_status;
      file.document_category = file.edit_category;
      file.rejection_reason = file.edit_status === 'rejected' ? file.edit_rejection_reason : null;
      file.assigned_points = file.edit_category === 'other' ? (Number(file.edit_manual_points) || 0) : null;

      file.original_status = file.edit_status;
      file.original_category = file.edit_category;
      file.original_rejection_reason = file.rejection_reason || '';
      file.original_manual_points = file.edit_manual_points;
    });

    saveSuccessMessage.value = 'Revisión y categorías guardadas correctamente';
    setTimeout(() => {
      saveSuccessMessage.value = '';
    }, 4000);
  } catch (err) {
    console.error('Error al guardar revisión de documentos:', err);
    alert(err.response?.data?.message || 'Error al guardar los cambios en los documentos');
  } finally {
    savingReviews.value = false;
  }
};

const toggleGooglePlacesVerification = async () => {
  googleVerifying.value = true;
  try {
    const newStatus = !isGooglePlaceVerified.value;
    const res = await axiosInstance.post(
      `/adm/establishments/${establishment.value.id}/verify-google-places`,
      { is_verified: newStatus, notes: 'Validación manual por el administrador' }
    );
    establishment.value.trust_score = res.data.trust_score;
    establishment.value.trust_level = res.data.trust_level;
    await fetchEstablishment(false);
  } catch (err) {
    alert(err.response?.data?.message || 'Error al validar ficha de Google Places');
  } finally {
    googleVerifying.value = false;
  }
};

const openPhoneModal = () => {
  phoneNotes.value = '';
  showPhoneModal.value = true;
};

const closePhoneModal = () => {
  showPhoneModal.value = false;
  phoneNotes.value = '';
};

const confirmPhoneCall = async () => {
  phoneVerifying.value = true;
  try {
    const res = await axiosInstance.post(
      `/adm/establishments/${establishment.value.id}/verify-phone-call`,
      { call_successful: true, notes: phoneNotes.value.trim() || 'Llamada de confirmación exitosa' }
    );
    establishment.value.trust_score = res.data.trust_score;
    establishment.value.trust_level = res.data.trust_level;
    closePhoneModal();
    await fetchEstablishment(false);
  } catch (err) {
    alert(err.response?.data?.message || 'Error al registrar llamada telefónica');
  } finally {
    phoneVerifying.value = false;
  }
};

const verifyEstablishment = async () => {
  if (!establishment.value || verifying.value) return;
  if (!confirm('¿Seguro que deseas activar este establecimiento y habilitar al vendedor?')) return;

  try {
    verifying.value = true;
    await axiosInstance.patch(`/adm/establishments/${establishment.value.id}/verify`);
    alert('Establecimiento activado correctamente');
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error verifying establishment:', err);
    alert(err.response?.data?.message || 'Error al verificar el establecimiento');
  } finally {
    verifying.value = false;
  }
};

const openRejectModal = () => {
  showRejectModal.value = true;
  rejectReason.value = '';
};

const closeRejectModal = () => {
  showRejectModal.value = false;
  rejectReason.value = '';
};

const rejectEstablishment = async () => {
  if (!establishment.value || rejecting.value) return;
  if (!rejectReason.value || rejectReason.value.trim().length < 10) {
    alert('Por favor ingresa una razón de al menos 10 caracteres');
    return;
  }

  try {
    rejecting.value = true;
    await axiosInstance.patch(`/adm/establishments/${establishment.value.id}/reject`, {
      reason: rejectReason.value.trim(),
    });
    alert('Establecimiento rechazado correctamente');
    closeRejectModal();
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error rejecting establishment:', err);
    alert(err.response?.data?.message || 'Error al rechazar el establecimiento');
  } finally {
    rejecting.value = false;
  }
};

const revokePreviewUrl = () => {
  if (previewBlobUrl.value) {
    URL.revokeObjectURL(previewBlobUrl.value);
    previewBlobUrl.value = null;
  }
};

const loadPreview = async (file) => {
  selectedPreviewFile.value = file;
  previewError.value = '';
  previewLoading.value = true;
  revokePreviewUrl();

  try {
    const response = await axiosInstance.get(`/admin/verification-files/${file.id}`, {
      responseType: 'blob',
    });
    const contentType = response.headers['content-type'] ||
      (isPdfFile(file.file_type) ? 'application/pdf' : 'image/jpeg');
    const blob = new Blob([response.data], { type: contentType });
    previewBlobUrl.value = URL.createObjectURL(blob);
  } catch (err) {
    console.warn('No se pudo cargar la vista previa:', err);
    previewError.value = 'No se pudo cargar la vista previa del documento.';
  } finally {
    previewLoading.value = false;
  }
};

const openFile = async (file) => {
  if (!file || openingFileId.value === file.id) return;
  openingFileId.value = file.id;

  const newTab = window.open('about:blank', '_blank');

  try {
    const response = await axiosInstance.get(`/admin/verification-files/${file.id}`, {
      responseType: 'blob',
    });

    const contentType = response.headers['content-type'] ||
      (isPdfFile(file.file_type) ? 'application/pdf' : 'image/jpeg');

    const blob = new Blob([response.data], { type: contentType });
    const blobUrl = URL.createObjectURL(blob);

    if (newTab) {
      newTab.location.href = blobUrl;
    } else {
      window.open(blobUrl, '_blank');
    }
  } catch (err) {
    if (newTab) newTab.close();
    console.error('Error abriendo archivo de verificación:', err);
    alert('No se pudo abrir el documento. Verifica tu conexión o que tu sesión esté activa.');
  } finally {
    openingFileId.value = null;
  }
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const d = new Date(dateString);
  return d.toLocaleDateString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const getStatusBadge = (status) => {
  if (status === 'approved') return { label: 'Comercio Aprobado', cls: 'bg-success/15 text-success' };
  if (status === 'rejected') return { label: 'Solicitud Rechazada', cls: 'bg-danger/15 text-danger' };
  return { label: 'Pendiente de verificación', cls: 'bg-warning/15 text-warning' };
};

onMounted(fetchEstablishment);
onBeforeUnmount(revokePreviewUrl);
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="h-14 bg-surface rounded-card w-1/2"></div>
      <div class="grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-6">
        <div class="h-96 bg-surface rounded-card"></div>
        <div class="h-96 bg-surface rounded-card"></div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error || !establishment"
      class="bg-surface border border-border-subtle rounded-card p-10 text-center space-y-3 shadow-card"
    >
      <p class="text-sm font-semibold text-danger">{{ error || 'Establecimiento no encontrado.' }}</p>
      <button
        type="button"
        class="h-9 px-4 rounded-full bg-brand text-surface text-xs font-bold cursor-pointer"
        @click="router.push({ name: 'new-sellers' })"
      >
        ← Volver a Solicitudes de Comercios
      </button>
    </div>

    <!-- Contenido Principal -->
    <template v-else>
      <!-- Top Breadcrumb + Título + Acciones Globales -->
      <header class="space-y-3">
        <button
          type="button"
          class="text-xs font-semibold text-brand hover:text-brand-hover transition-colors cursor-pointer"
          @click="router.push({ name: 'new-sellers' })"
        >
          ← Volver a Solicitudes de Comercios
        </button>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2.5">
              <h1 class="text-2xl sm:text-3xl font-extrabold text-content-strong tracking-tight font-heading">
                {{ establishment.name }}
              </h1>
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                :class="getStatusBadge(establishment.verification_status).cls"
              >
                {{ getStatusBadge(establishment.verification_status).label }}
              </span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-surface-sunken text-content-secondary text-xs font-bold tabular-nums">
                #EST-{{ establishment.id }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-content-secondary">
              {{ establishment.establishment_type?.name || 'Comercio gastronómico' }} • Postulado el {{ formatDateTime(establishment.created_at) }}
            </p>
          </div>

          <div
            v-if="establishment.verification_status === 'pending'"
            class="flex flex-wrap items-center gap-2.5 shrink-0"
          >
            <button
              type="button"
              :disabled="rejecting || verifying"
              class="h-10 px-5 rounded-full bg-surface border border-brand text-brand hover:bg-brand/10 text-xs font-bold font-heading transition-colors cursor-pointer disabled:opacity-50"
              @click="openRejectModal"
            >
              Rechazar solicitud
            </button>
            <button
              type="button"
              :disabled="verifying || rejecting"
              class="h-10 px-6 rounded-full bg-success hover:opacity-90 text-surface text-xs font-bold font-heading shadow-xs inline-flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
              @click="verifyEstablishment"
            >
              <span>✓ {{ verifying ? 'Activando...' : 'Aprobar y Activar Comercio' }}</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Layout 2 Columnas -->
      <div class="grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-6 items-start">
        <!-- COLUMNA IZQUIERDA -->
        <div class="space-y-5">
          <!-- Card 1: Datos del Establecimiento y Titular -->
          <section class="bg-surface border border-border-subtle rounded-card p-5 shadow-2xs space-y-4">
            <h2 class="text-[15px] font-extrabold text-content-strong font-heading">
              Datos del Establecimiento y Titular
            </h2>

            <dl class="space-y-2.5 text-xs">
              <div class="flex items-start justify-between gap-3">
                <dt class="text-content-secondary shrink-0">Titular / Solicitante</dt>
                <dd class="text-content-strong font-semibold text-right">
                  {{ establishment.user?.name }} {{ establishment.user?.last_name || '' }}
                  <span v-if="establishment.user?.email" class="block text-[11px] font-normal text-content-secondary">
                    {{ establishment.user.email }}
                  </span>
                </dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-content-secondary shrink-0">Dirección física</dt>
                <dd class="text-content-strong font-semibold text-right">
                  {{ establishment.address || 'Sin dirección' }}
                </dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-content-secondary shrink-0">Teléfono comercial</dt>
                <dd class="text-content-strong font-semibold text-right tabular-nums">
                  {{ availablePhoneNumber || 'No informado' }}
                </dd>
              </div>
            </dl>

            <!-- Caja Ubicación Google Maps -->
            <div class="rounded-xl bg-surface-sunken p-4 text-center space-y-1.5">
              <div class="w-6 h-6 mx-auto text-brand flex items-center justify-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
              </div>
              <p v-if="establishment.latitude && establishment.longitude" class="text-[11px] font-mono text-content-secondary">
                Lat: {{ Number(establishment.latitude).toFixed(4) }} • Lng: {{ Number(establishment.longitude).toFixed(4) }}
              </p>
              <a
                v-if="googleMapsUrl"
                :href="googleMapsUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block text-xs font-bold text-brand hover:underline"
              >
                Abrir ubicación en Google Maps ↗
              </a>
            </div>
          </section>

          <!-- Card 2: Trust Score de Verificación -->
          <section class="bg-surface border border-border-subtle rounded-card p-5 shadow-2xs space-y-4">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h2 class="text-[15px] font-extrabold text-content-strong font-heading">
                  Trust Score de Verificación
                </h2>
                <p class="text-[11.5px] text-content-secondary">
                  Umbral recomendado ≥ 70 pts para aprobar
                </p>
              </div>
              <div class="text-right tabular-nums shrink-0">
                <span
                  class="text-2xl font-extrabold font-heading"
                  :class="
                    calculatedTrustLevel === 'green'
                      ? 'text-success'
                      : calculatedTrustLevel === 'yellow'
                        ? 'text-warning'
                        : 'text-danger'
                  "
                >
                  {{ totalCalculatedPoints }}
                </span>
                <span class="text-xs font-semibold text-content-secondary"> / {{ totalPossiblePoints }}</span>
              </div>
            </div>

            <div class="w-full h-2 rounded-full bg-surface-sunken overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="
                  calculatedTrustLevel === 'green'
                    ? 'bg-success'
                    : calculatedTrustLevel === 'yellow'
                      ? 'bg-warning'
                      : 'bg-danger'
                "
                :style="{ width: `${trustProgressPercentage}%` }"
              ></div>
            </div>

            <ul class="space-y-2 text-xs">
              <li
                v-for="item in trustScoreBreakdown"
                :key="item.id"
                class="flex items-center justify-between gap-2"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :class="
                      item.isVerified
                        ? 'bg-success'
                        : item.isRejected
                          ? 'bg-brand'
                          : item.isUploaded
                            ? 'bg-warning'
                            : 'bg-border-subtle'
                    "
                  ></span>
                  <span class="text-content-strong truncate">{{ item.name }}</span>
                </div>
                <span
                  class="font-bold tabular-nums shrink-0"
                  :class="
                    item.isVerified
                      ? 'text-success'
                      : item.isRejected
                        ? 'text-brand'
                        : 'text-content-secondary'
                  "
                >
                  {{ item.isVerified ? `+${item.earnedPoints} pts` : `0 / ${item.maxPoints} pts` }}
                </span>
              </li>
            </ul>
          </section>

          <!-- Card 3: Validaciones Externas -->
          <section class="bg-surface border border-border-subtle rounded-card p-5 shadow-2xs space-y-3">
            <h2 class="text-[15px] font-extrabold text-content-strong font-heading">
              Validaciones Externas
            </h2>

            <!-- Google Places -->
            <div class="rounded-xl bg-surface-sunken p-3.5 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-bold text-content-strong">
                  {{ isGooglePlaceVerified ? '★ Google Places verificado' : 'Ficha de Google Places' }}
                  <span v-if="establishment.google_place_data?.rating" class="font-semibold text-content-secondary">
                    ({{ establishment.google_place_data.rating }} ★)
                  </span>
                </p>
                <p class="text-[11px]" :class="isGooglePlaceVerified ? 'text-success font-medium' : 'text-content-secondary'">
                  {{ isGooglePlaceVerified ? 'Coincide nombre y dirección comercial • +20 pts' : hasGooglePlace ? 'Pendiente de confirmación (+20 pts)' : 'Sin ficha vinculada' }}
                </p>
              </div>
              <button
                type="button"
                :disabled="googleVerifying || !hasGooglePlace"
                class="h-8 px-3.5 rounded-full bg-surface border border-border-subtle hover:border-brand text-[11px] font-semibold text-content-strong shrink-0 cursor-pointer disabled:opacity-40"
                @click="toggleGooglePlacesVerification"
              >
                {{ googleVerifying ? '...' : isGooglePlaceVerified ? 'Re-verificar' : 'Verificar' }}
              </button>
            </div>

            <!-- Llamada Telefónica -->
            <div class="rounded-xl bg-surface-sunken p-3.5 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-bold text-content-strong truncate">
                  {{ availablePhoneNumber ? `Llamada al ${availablePhoneNumber}` : 'Validación telefónica' }}
                </p>
                <p class="text-[11px]" :class="isPhoneCallVerified ? 'text-success font-medium' : 'text-content-secondary'">
                  {{ isPhoneCallVerified ? 'Llamada confirmada • +30 pts' : 'Pendiente de confirmación telefónica (+30 pts)' }}
                </p>
              </div>
              <button
                v-if="!isPhoneCallVerified"
                type="button"
                :disabled="phoneVerifying"
                class="h-8 px-3.5 rounded-full bg-brand hover:bg-brand-hover text-surface text-[11px] font-bold shrink-0 cursor-pointer disabled:opacity-50"
                @click="openPhoneModal"
              >
                Registrar llamada
              </button>
              <span v-else class="px-2.5 py-1 rounded-full bg-success/15 text-success text-[11px] font-bold shrink-0">
                Confirmada
              </span>
            </div>
          </section>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="space-y-5">
          <!-- Header Revisión Documentación -->
          <section class="bg-surface border border-border-subtle rounded-card p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-[15px] font-extrabold text-content-strong font-heading">
                Revisión de Documentación Adjunta ({{ verifiedFilesCount }} de {{ totalFilesCount }} aprobados)
              </h2>
              <p v-if="saveSuccessMessage" class="text-xs font-semibold text-success mt-0.5">
                ✓ {{ saveSuccessMessage }}
              </p>
              <p v-else-if="hasUnsavedChanges" class="text-xs font-semibold text-warning mt-0.5">
                Tienes cambios locales sin guardar • Modificá estado o categoría y guardá el lote
              </p>
              <p v-else class="text-xs text-content-secondary mt-0.5">
                Validá cada documento o reclasificá su categoría antes de habilitar el comercio.
              </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button
                type="button"
                :disabled="!hasUnsavedChanges || savingReviews"
                class="h-9 px-4 rounded-full bg-surface-sunken text-xs font-semibold text-content-strong disabled:opacity-40 cursor-pointer"
                @click="revertLocalChanges"
              >
                Descartar
              </button>
              <button
                type="button"
                :disabled="!hasUnsavedChanges || savingReviews"
                class="h-9 px-5 rounded-full bg-surface-darkest hover:opacity-90 text-surface text-xs font-bold font-heading disabled:opacity-40 cursor-pointer"
                @click="saveDocumentReviews"
              >
                {{ savingReviews ? 'Guardando...' : 'Guardar revisiones' }}
              </button>
            </div>
          </section>

          <!-- Lista de Documentos -->
          <div v-if="totalFilesCount === 0" class="bg-surface border border-border-subtle rounded-card p-8 text-center text-xs text-content-secondary">
            El comercio todavía no adjuntó archivos de verificación.
          </div>

          <div v-else class="space-y-3">
            <article
              v-for="file in establishment.verification_files"
              :key="file.id"
              class="bg-surface border rounded-card px-5 py-4 shadow-2xs transition-colors flex flex-col xl:flex-row xl:items-center justify-between gap-4"
              :class="selectedPreviewFile?.id === file.id ? 'border-brand' : 'border-border-subtle'"
            >
              <!-- Izquierda: Icono + Info + Reclasificación -->
              <div class="flex items-start gap-3.5 min-w-0 flex-1">
                <div class="w-10 h-10 rounded-xl bg-surface-sunken flex items-center justify-center text-content-strong shrink-0 mt-0.5">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>

                <div class="min-w-0 flex-1 space-y-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-extrabold text-content-strong font-heading">
                      {{ getDocumentCategoryLabel(file.edit_category) }}
                    </h3>
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold tabular-nums"
                      :class="
                        file.edit_status === 'accepted'
                          ? 'bg-success/15 text-success'
                          : file.edit_status === 'rejected'
                            ? 'bg-brand/15 text-brand'
                            : 'bg-warning/15 text-warning'
                      "
                    >
                      {{ file.edit_status === 'accepted' ? `+${getDocumentMaxPoints(file)} pts` : file.edit_status === 'rejected' ? `0 / ${getDocumentMaxPoints(file)} pts` : `+${getDocumentMaxPoints(file)} pts` }}
                    </span>
                  </div>

                  <p class="text-xs text-content-secondary truncate">
                    {{ getFileDisplayName(file) }} •
                    <span v-if="file.edit_status === 'rejected' && file.edit_rejection_reason" class="text-brand font-medium">
                      Rechazado: "{{ file.edit_rejection_reason }}"
                    </span>
                    <span v-else-if="file.edit_status === 'pending'">
                      Pendiente de validación visual
                    </span>
                    <span v-else>
                      Subido el {{ formatDateTime(file.created_at) }}
                    </span>
                  </p>

                  <!-- Selector rápido de categoría y puntaje manual si es "other" -->
                  <div class="flex flex-wrap items-center gap-2 pt-1">
                    <select
                      v-model="file.edit_category"
                      class="h-7 px-2.5 rounded-lg bg-canvas border border-border-subtle text-[11px] font-medium text-content-strong focus:outline-none focus:border-brand cursor-pointer"
                    >
                      <option v-for="opt in documentCategoryOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>

                    <div v-if="file.edit_category === 'other'" class="inline-flex items-center gap-1 bg-canvas border border-border-subtle rounded-lg px-2 h-7 text-[11px]">
                      <span class="text-content-secondary">Pts:</span>
                      <button type="button" class="px-1 font-bold hover:text-brand cursor-pointer" @click="changeManualPoints(file, -5)">-</button>
                      <input
                        v-model.number="file.edit_manual_points"
                        type="number"
                        min="0"
                        max="50"
                        class="w-9 text-center bg-transparent font-bold text-content-strong focus:outline-none"
                        @blur="sanitizeManualPoints(file)"
                      />
                      <button type="button" class="px-1 font-bold hover:text-brand cursor-pointer" @click="changeManualPoints(file, 5)">+</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Derecha: Ver / Aprobar / Rechazar -->
              <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                <button
                  type="button"
                  class="h-8 px-3.5 rounded-full bg-surface-sunken hover:bg-border-subtle/50 text-content-strong text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                  @click="loadPreview(file)"
                >
                  <span>👁 Ver</span>
                </button>

                <button
                  type="button"
                  class="h-8 px-4 rounded-full text-xs font-bold transition-colors cursor-pointer"
                  :class="
                    file.edit_status === 'accepted'
                      ? 'bg-success text-surface shadow-xs'
                      : 'bg-surface border border-border-subtle text-content-strong hover:border-success hover:text-success'
                  "
                  @click="toggleVerifyLocal(file)"
                >
                  ✓ {{ file.edit_status === 'accepted' ? 'Aprobado' : 'Aprobar' }}
                </button>

                <button
                  type="button"
                  class="h-8 px-4 rounded-full text-xs font-bold transition-colors cursor-pointer"
                  :class="
                    file.edit_status === 'rejected'
                      ? 'bg-brand text-surface shadow-xs'
                      : 'bg-surface border border-border-subtle text-content-strong hover:border-brand hover:text-brand'
                  "
                  @click="openFileRejectModal(file)"
                >
                  × {{ file.edit_status === 'rejected' ? 'Rechazado' : 'Rechazar' }}
                </button>
              </div>
            </article>
          </div>

          <!-- Vista Previa Rápida Embebida -->
          <section
            v-if="selectedPreviewFile"
            class="bg-surface border border-border-subtle rounded-card p-5 shadow-2xs space-y-3.5"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h3 class="text-sm font-extrabold text-content-strong font-heading">
                Vista previa rápida • {{ getFileDisplayName(selectedPreviewFile) }}
              </h3>
              <button
                type="button"
                class="text-xs font-bold text-brand hover:underline cursor-pointer"
                @click="openFile(selectedPreviewFile)"
              >
                Abrir documento completo en pestaña ↗
              </button>
            </div>

            <div class="rounded-xl bg-surface-sunken border border-border-subtle/60 overflow-hidden min-h-[220px] flex items-center justify-center">
              <div v-if="previewLoading" class="text-xs text-content-secondary py-12">
                Cargando vista previa...
              </div>
              <div v-else-if="previewError" class="text-xs text-danger py-12 px-4 text-center">
                {{ previewError }}
              </div>
              <img
                v-else-if="previewBlobUrl && isImageFile(selectedPreviewFile.file_type)"
                :src="previewBlobUrl"
                :alt="getFileDisplayName(selectedPreviewFile)"
                class="max-h-[420px] w-auto object-contain mx-auto"
              />
              <iframe
                v-else-if="previewBlobUrl && isPdfFile(selectedPreviewFile.file_type)"
                :src="previewBlobUrl"
                class="w-full h-[380px] border-0"
                title="Vista previa PDF"
              />
              <div v-else class="text-xs text-content-secondary py-12 text-center">
                Vista previa no disponible para este formato. Usá "Abrir documento completo en pestaña ↗".
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>

    <!-- Modal Rechazar Archivo Individual -->
    <Teleport to="body">
      <div
        v-if="showFileRejectModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-darkest/60 backdrop-blur-xs"
        @click.self="closeFileRejectModal"
      >
        <div class="w-full max-w-md bg-surface border border-border-subtle rounded-modal p-6 shadow-card space-y-4">
          <h4 class="text-base font-extrabold text-content-strong font-heading">
            Rechazar documento
          </h4>
          <p class="text-xs text-content-secondary">
            Indicá el motivo de rechazo para <strong>{{ selectedFileForReject ? getFileDisplayName(selectedFileForReject) : '' }}</strong>.
          </p>
          <textarea
            v-model="fileRejectReason"
            rows="3"
            placeholder="Ej: Imagen borrosa, no se distingue el período fiscal..."
            class="w-full p-3 rounded-xl bg-canvas border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-brand resize-none"
          />
          <div class="flex items-center justify-end gap-2">
            <button type="button" class="h-9 px-4 rounded-full bg-surface-sunken text-xs font-semibold cursor-pointer" @click="closeFileRejectModal">
              Cancelar
            </button>
            <button type="button" class="h-9 px-5 rounded-full bg-brand text-surface text-xs font-bold cursor-pointer" @click="confirmRejectFile">
              Confirmar rechazo
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Registrar Llamada Telefónica -->
    <Teleport to="body">
      <div
        v-if="showPhoneModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-darkest/60 backdrop-blur-xs"
        @click.self="closePhoneModal"
      >
        <div class="w-full max-w-md bg-surface border border-border-subtle rounded-modal p-6 shadow-card space-y-4">
          <h4 class="text-base font-extrabold text-content-strong font-heading">
            Registrar validación telefónica
          </h4>
          <p class="text-xs text-content-secondary">
            Confirmá que te comunicaste con el titular al <strong>{{ availablePhoneNumber || 'teléfono registrado' }}</strong> (+30 pts).
          </p>
          <textarea
            v-model="phoneNotes"
            rows="3"
            placeholder="Notas de la llamada (opcional)..."
            class="w-full p-3 rounded-xl bg-canvas border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-brand resize-none"
          />
          <div class="flex items-center justify-end gap-2">
            <button type="button" class="h-9 px-4 rounded-full bg-surface-sunken text-xs font-semibold cursor-pointer" @click="closePhoneModal">
              Cancelar
            </button>
            <button
              type="button"
              :disabled="phoneVerifying"
              class="h-9 px-5 rounded-full bg-brand text-surface text-xs font-bold cursor-pointer disabled:opacity-50"
              @click="confirmPhoneCall"
            >
              {{ phoneVerifying ? 'Guardando...' : 'Confirmar llamada' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Rechazar Solicitud Global -->
    <Teleport to="body">
      <div
        v-if="showRejectModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-darkest/60 backdrop-blur-xs"
        @click.self="closeRejectModal"
      >
        <div class="w-full max-w-md bg-surface border border-border-subtle rounded-modal p-6 shadow-card space-y-4">
          <h4 class="text-base font-extrabold text-danger font-heading">
            Rechazar solicitud de comercio
          </h4>
          <p class="text-xs text-content-secondary">
            El solicitante recibirá el motivo por correo electrónico y podrá corregir su documentación.
          </p>
          <textarea
            v-model="rejectReason"
            rows="4"
            placeholder="Ingresá un motivo detallado (mínimo 10 caracteres)..."
            class="w-full p-3 rounded-xl bg-canvas border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-danger resize-none"
          />
          <div class="flex items-center justify-end gap-2">
            <button type="button" class="h-9 px-4 rounded-full bg-surface-sunken text-xs font-semibold cursor-pointer" @click="closeRejectModal">
              Cancelar
            </button>
            <button
              type="button"
              :disabled="rejecting"
              class="h-9 px-5 rounded-full bg-danger text-surface text-xs font-bold cursor-pointer disabled:opacity-50"
              @click="rejectEstablishment"
            >
              {{ rejecting ? 'Rechazando...' : 'Rechazar comercio' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
