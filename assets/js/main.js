// Main JavaScript functionality

// Search and filter functionality for homepage
function filterJobs() {
  const searchTerm = document.getElementById("searchInput")?.value.toLowerCase() || ""
  const departmentFilter = document.getElementById("departmentFilter")?.value || ""
  const locationFilter = document.getElementById("locationFilter")?.value || ""

  const jobCards = document.querySelectorAll(".job-card")
  let visibleCount = 0

  jobCards.forEach((card) => {
    const title = card.querySelector("h4")?.textContent.toLowerCase() || ""
    const department = card.dataset.department || ""
    const location = card.dataset.location || ""

    const matchesSearch =
      title.includes(searchTerm) ||
      department.toLowerCase().includes(searchTerm) ||
      location.toLowerCase().includes(searchTerm)
    const matchesDepartment = !departmentFilter || department === departmentFilter
    const matchesLocation = !locationFilter || location.includes(locationFilter)

    if (matchesSearch && matchesDepartment && matchesLocation) {
      card.style.display = "block"
      visibleCount++
    } else {
      card.style.display = "none"
    }
  })

  // Update job count
  const jobCount = document.querySelector(".job-count")
  if (jobCount) {
    jobCount.textContent = `${visibleCount} positions available`
  }
}

// Auto-save form data to localStorage
function autoSaveForm(formId) {
  const form = document.getElementById(formId)
  if (!form) return

  const inputs = form.querySelectorAll("input, select, textarea")

  // Load saved data
  inputs.forEach((input) => {
    const savedValue = localStorage.getItem(`${formId}_${input.name}`)
    if (savedValue && input.type !== "file") {
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
      if (input.type !== "file") {
        localStorage.setItem(`${formId}_${input.name}`, input.value)
      }
    })
  })
}

// Clear auto-saved form data
function clearAutoSave(formId) {
  const form = document.getElementById(formId)
  if (!form) return

  const inputs = form.querySelectorAll("input, select, textarea")
  inputs.forEach((input) => {
    localStorage.removeItem(`${formId}_${input.name}`)
  })
}

// File upload handling
function setupFileUpload() {
  const fileUploadArea = document.getElementById("fileUploadArea")
  const fileInput = document.getElementById("resume")

  if (!fileUploadArea || !fileInput) return

  // Click to upload
  fileUploadArea.addEventListener("click", () => {
    fileInput.click()
  })

  // Drag and drop
  fileUploadArea.addEventListener("dragover", (e) => {
    e.preventDefault()
    fileUploadArea.style.borderColor = "#3b82f6"
    fileUploadArea.style.backgroundColor = "#f0f9ff"
  })

  fileUploadArea.addEventListener("dragleave", (e) => {
    e.preventDefault()
    fileUploadArea.style.borderColor = "#d1d5db"
    fileUploadArea.style.backgroundColor = ""
  })

  fileUploadArea.addEventListener("drop", (e) => {
    e.preventDefault()
    fileUploadArea.style.borderColor = "#d1d5db"
    fileUploadArea.style.backgroundColor = ""

    const files = e.dataTransfer.files
    if (files.length > 0) {
      fileInput.files = files
      updateFileDisplay(files[0])
    }
  })

  // File input change
  fileInput.addEventListener("change", (e) => {
    if (e.target.files.length > 0) {
      updateFileDisplay(e.target.files[0])
    }
  })

  function updateFileDisplay(file) {
    const content = fileUploadArea.querySelector(".file-upload-content")
    if (file.type === "application/pdf" && file.size <= 10 * 1024 * 1024) {
      content.innerHTML = `
                <i class="fas fa-file-pdf" style="color: #dc2626;"></i>
                <p><strong>${file.name}</strong></p>
                <p class="file-info">PDF • ${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            `
    } else {
      content.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                <p style="color: #dc2626;"><strong>Invalid file</strong></p>
                <p class="file-info">Please select a PDF file under 10MB</p>
            `
    }
  }
}

// Form validation
function validateForm(formId) {
  const form = document.getElementById(formId)
  if (!form) return true

  let isValid = true
  const requiredFields = form.querySelectorAll("[required]")

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

  // Email validation
  const emailFields = form.querySelectorAll('input[type="email"]')
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

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Smooth scrolling for anchor links
function setupSmoothScrolling() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
    })
  })
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  // Setup search functionality
  const searchInput = document.getElementById("searchInput")
  if (searchInput) {
    searchInput.addEventListener("input", filterJobs)
  }

  const departmentFilter = document.getElementById("departmentFilter")
  if (departmentFilter) {
    departmentFilter.addEventListener("change", filterJobs)
  }

  const locationFilter = document.getElementById("locationFilter")
  if (locationFilter) {
    locationFilter.addEventListener("change", filterJobs)
  }

  // Setup file upload
  setupFileUpload()

  // Setup auto-save for application form
  autoSaveForm("applicationForm")

  // Setup smooth scrolling
  setupSmoothScrolling()

  // Setup form validation on submit
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!validateForm(form.id)) {
        e.preventDefault()
      }
    })
  })
})

// Utility functions
function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `

  document.body.appendChild(notification)

  setTimeout(() => {
    notification.remove()
  }, 5000)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "ksh",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// Export functions for use in other scripts
window.filterJobs = filterJobs
window.autoSaveForm = autoSaveForm
window.clearAutoSave = clearAutoSave
window.validateForm = validateForm
window.showNotification = showNotification
window.formatDate = formatDate
window.formatCurrency = formatCurrency
