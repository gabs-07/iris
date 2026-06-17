
const CEDULA_API_BASE_URL = window.IRIS_CEDULA_API_URL || "/api/cedulas/verificar";

async function verifyMexicanCedula(cedula) {
  if (!cedula) {
    return { success: false, valid: false, message: "Ingresa una cédula mexicana para verificar.", specialty: null, rawResponse: null };
  }

  if (!/^\d{6,8}$/.test(cedula)) {
    return { success: false, valid: false, message: "La cédula debe tener entre 6 y 8 dígitos numéricos.", specialty: null, rawResponse: null };
  }

  try {
    const response = await fetch(`${CEDULA_API_BASE_URL}?numero=${encodeURIComponent(cedula)}`, {
      headers: { "Accept": "application/json" },
      credentials: "same-origin",
    });
    if (!response.ok) throw new Error("Error al conectar con el servicio de cédulas.");
    const data = await response.json();
    return {
      success: true,
      valid: Boolean(data.valid),
      message: data.message || "Verificación completada.",
      specialty: data.specialty || null,
      rawResponse: data,
    };
  } catch (error) {
    console.error("verifyMexicanCedula error:", error);
    return { success: false, valid: false, message: "No se pudo verificar la cédula. Configura el endpoint de verificación en Laravel.", specialty: null, rawResponse: null };
  }
}
