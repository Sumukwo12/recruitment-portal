// Application form specific JavaScript

let currentStep = 1
const totalSteps = 4

function updateProgress() {
  const progress = (currentStep / totalSteps) * 100
  const progressFill = document.getElementById("progressFill")
  const currentStepElement = document.getElementById("currentStep")
  const progressPercent = document.getElementById("progressPercent")

  if (progressFill) progressFill.style.width = progress + "%"
  if (currentStepElement) currentStepElement.textContent = currentStep
  if (progressPercent) progressPercent.textContent = Math.round(progress)
}

function showStep(step) {
  // Hide all steps
  document.querySelectorAll(".form-step").forEach((stepElement) => {
    stepElement.classList.remove("active")
  })

  // Show current step
  const currentStepElement = document.getElementById(`step${step}`)
  if (currentStepElement) {
    currentStepElement.classList.add("active")
  }

  // Update navigation buttons
  const prevBtn = document.getElementById("prevBtn")
  const nextBtn = document.getElementById("nextBtn")
  const submitBtn = document.getElementById("submitBtn")

  if (prevBtn) {
    prevBtn.style.display = step === 1 ? "none" : "inline-flex"
  }

  if (nextBtn) {
    nextBtn.style.display = step === totalSteps ? "none" : "inline-flex"
  }

  if (submitBtn) {
    submitBtn.style.display = step === totalSteps ? "inline-flex" : "none"
  }

  updateProgress()
}

function validateCurrentStep() {
  const currentStepElement = document.getElementById(`step${currentStep}`)
  if (!currentStepElement) return true

  let isValid = true
  const requiredFields = currentStepElement.querySelectorAll("[required]")

  requiredFields.forEach((field) => {
    const errorElement = field.parentNode.querySelector(".error-message")

    if (!field.value.trim()) {
      field.classList.add("error")
      if (errorElement) {
        errorElement.textContent = "This field is required"
      }
      isValid = false
    } else {
      field.classList.remove("error")
      if (errorElement) {
        errorElement.textContent = ""
      }
    }
  })

  // Special validation for file upload in step 2
  if (currentStep === 2) {
    const fileInput = document.getElementById("resume")
    if (fileInput && !fileInput.files.length) {
      const errorElement = fileInput.parentNode.querySelector(".error-message")
      if (errorElement) {
        errorElement.textContent = "Resume is required"
      }
      isValid = false
    }
  }

  // Email validation
  const emailFields = currentStepElement.querySelectorAll('input[type="email"]')
  emailFields.forEach((field) => {
    if (field.value && !isValidEmail(field.value)) {
      field.classList.add("error")
      const errorElement = field.parentNode.querySelector(".error-message")
      if (errorElement) {
        errorElement.textContent = "Please enter a valid email address"
      }
      isValid = false
    }
  })

  return isValid
}

function changeStep(direction) {
  if (direction === 1 && !validateCurrentStep()) {
    return
  }

  const newStep = currentStep + direction

  if (newStep >= 1 && newStep <= totalSteps) {
    currentStep = newStep
    showStep(currentStep)

    // Scroll to top of form
    document.querySelector(".application-form-wrapper").scrollIntoView({
      behavior: "smooth",
      block: "start",
    })
  }
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// File upload preview
function setupFilePreview() {
  const fileInput = document.getElementById("resume")
  const fileUploadArea = document.getElementById("fileUploadArea")

  if (!fileInput || !fileUploadArea) return

  fileInput.addEventListener("change", (e) => {
    const file = e.target.files[0]
    if (file) {
      updateFilePreview(file)
    }
  })

  function updateFilePreview(file) {
    const content = fileUploadArea.querySelector(".file-upload-content")

    if (file.type === "application/pdf") {
      if (file.size <= 10 * 1024 * 1024) {
        // 10MB
        content.innerHTML = `
                    <i class="fas fa-file-pdf" style="color: #dc2626; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <p><strong>File Selected</strong></p>
                    <p style="color: #374151; margin-bottom: 0.25rem;">${file.name}</p>
                    <p class="file-info">PDF • ${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                `

        // Remove error styling
        fileInput.classList.remove("error")
        const errorElement = fileInput.parentNode.querySelector(".error-message")
        if (errorElement) {
          errorElement.textContent = ""
        }
      } else {
        content.innerHTML = `
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <p style="color: #dc2626;"><strong>File too large</strong></p>
                    <p class="file-info">Please select a PDF file under 10MB</p>
                `
        fileInput.value = ""
      }
    } else {
      content.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #dc2626; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <p style="color: #dc2626;"><strong>Invalid file type</strong></p>
                <p class="file-info">Please select a PDF file only</p>
            `
      fileInput.value = ""
    }
  }
}

// Auto-save functionality
function setupAutoSave() {
  const form = document.getElementById("applicationForm")
  if (!form) return

  const inputs = form.querySelectorAll('input:not([type="file"]), select, textarea')

  // Load saved data
  inputs.forEach((input) => {
    const savedValue = localStorage.getItem(`application_${input.name}`)
    if (savedValue) {
      if (input.type === "radio") {
        if (input.value === savedValue) {
          input.checked = true
        }
      } else {
        input.value = savedValue
      }
    }
  })

  // Save data on change
  inputs.forEach((input) => {
    input.addEventListener("change", () => {
      localStorage.setItem(`application_${input.name}`, input.value)
    })

    input.addEventListener("input", () => {
      localStorage.setItem(`application_${input.name}`, input.value)
    })
  })
}

// Clear auto-saved data on successful submission
function clearApplicationData() {
  const form = document.getElementById("applicationForm")
  if (!form) return

  const inputs = form.querySelectorAll('input:not([type="file"]), select, textarea')
  inputs.forEach((input) => {
    localStorage.removeItem(`application_${input.name}`)
  })
}

// Initialize application form
document.addEventListener("DOMContentLoaded", () => {
  // Show initial step
  showStep(1)

  // Setup file preview
  setupFilePreview()

  // Setup auto-save
  setupAutoSave()

  // Form submission handling
  const form = document.getElementById("applicationForm")
  if (form) {
    form.addEventListener("submit", (e) => {
      if (!validateCurrentStep()) {
        e.preventDefault()
        return false
      }

      // Show loading state
      const submitBtn = document.getElementById("submitBtn")
      if (submitBtn) {
        submitBtn.disabled = true
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...'
      }

      // Clear auto-saved data on successful submission
      setTimeout(() => {
        clearApplicationData()
      }, 1000)
    })
  }

  // Keyboard navigation
  document.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && e.ctrlKey) {
      // Ctrl+Enter to go to next step
      if (currentStep < totalSteps) {
        changeStep(1)
      }
    }
  })
})

// Export functions
window.changeStep = changeStep
window.validateCurrentStep = validateCurrentStep
window.clearApplicationData = clearApplicationData
