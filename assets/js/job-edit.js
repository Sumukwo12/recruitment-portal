// Job editing JavaScript functionality

let autoSaveTimeout
let questionIndex = 0

// Initialize job editing
document.addEventListener("DOMContentLoaded", () => {
  // Set initial question index
  const existingQuestions = document.querySelectorAll(".screening-question-item")
  questionIndex = existingQuestions.length

  // Setup auto-save
  setupAutoSave()

  // Setup character counting
  setupCharacterCounting()

  // Setup form validation
  setupFormValidation()

  // Load saved draft data
  loadDraftData()
})

// Auto-save functionality
function setupAutoSave() {
  const form = document.getElementById("editJobForm")
  const inputs = form.querySelectorAll("input, select, textarea")

  inputs.forEach((input) => {
    input.addEventListener("input", () => {
      clearTimeout(autoSaveTimeout)
      autoSaveTimeout = setTimeout(autoSaveJob, 2000) // Auto-save after 2 seconds of inactivity
    })
  })
}

function autoSaveJob() {
  const indicator = document.getElementById("autoSaveIndicator")
  indicator.style.display = "block"

  const form = document.getElementById("editJobForm")
  const formData = new FormData(form)

  // Save to localStorage as backup
  const draftData = {}
  for (const [key, value] of formData.entries()) {
    draftData[key] = value
  }
  localStorage.setItem("job_edit_draft", JSON.stringify(draftData))

  // Hide indicator after 2 seconds
  setTimeout(() => {
    indicator.style.display = "none"
  }, 2000)
}

function loadDraftData() {
  const draftData = localStorage.getItem("job_edit_draft")
  if (draftData) {
    try {
      const data = JSON.parse(draftData)
      // Only load if user confirms
      if (confirm("Found unsaved changes. Would you like to restore them?")) {
        Object.keys(data).forEach((key) => {
          const input = document.querySelector(`[name="${key}"]`)
          if (input && input.value === "") {
            input.value = data[key]
          }
        })
      }
    } catch (e) {
      console.error("Error loading draft data:", e)
    }
  }
}

// Character counting
function setupCharacterCounting() {
  const description = document.getElementById("description")
  const counter = document.getElementById("descriptionCount")

  function updateCount() {
    counter.textContent = description.value.length

    // Color coding based on length
    if (description.value.length < 100) {
      counter.style.color = "#dc2626" // Red - too short
    } else if (description.value.length < 500) {
      counter.style.color = "#f59e0b" // Yellow - good
    } else {
      counter.style.color = "#10b981" // Green - excellent
    }
  }

  description.addEventListener("input", updateCount)
  updateCount() // Initial count
}

// Form validation
function setupFormValidation() {
  const form = document.getElementById("editJobForm")

  form.addEventListener("submit", (e) => {
    if (!validateForm()) {
      e.preventDefault()
      showNotification("Please fix the errors before submitting", "error")
    }
  })
}

function validateForm() {
  const requiredFields = ["title", "department", "location", "type", "description", "deadline", "status"]
  let isValid = true

  // Clear previous errors
  document.querySelectorAll(".error-message").forEach((error) => error.remove())
  document.querySelectorAll(".error").forEach((field) => field.classList.remove("error"))

  requiredFields.forEach((fieldName) => {
    const field = document.querySelector(`[name="${fieldName}"]`)
    if (!field.value.trim()) {
      showFieldError(field, "This field is required")
      isValid = false
    }
  })

  // Validate salary range
  const salaryMin = document.getElementById("salary_min")
  const salaryMax = document.getElementById("salary_max")

  if (salaryMin.value && salaryMax.value && Number.parseFloat(salaryMin.value) > Number.parseFloat(salaryMax.value)) {
    showFieldError(salaryMax, "Maximum salary must be greater than minimum salary")
    isValid = false
  }

  // Validate deadline
  const deadline = document.getElementById("deadline")
  if (deadline.value && new Date(deadline.value) < new Date()) {
    showFieldError(deadline, "Deadline cannot be in the past")
    isValid = false
  }

  return isValid
}

function showFieldError(field, message) {
  field.classList.add("error")
  const errorElement = document.createElement("span")
  errorElement.className = "error-message"
  errorElement.textContent = message
  field.parentNode.appendChild(errorElement)
}

// Screening questions management
function addScreeningQuestion() {
  const container = document.getElementById("screeningQuestions")
  const noQuestions = container.querySelector(".no-questions")

  if (noQuestions) {
    noQuestions.remove()
  }

  const questionHtml = `
        <div class="screening-question-item" data-index="${questionIndex}">
            <div class="question-header">
                <span class="question-number">Question ${questionIndex + 1}</span>
                <button type="button" class="btn btn-small btn-outline btn-danger" onclick="removeScreeningQuestion(${questionIndex})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="form-group">
                <label>Question Text</label>
                <input type="text" name="screening_questions[${questionIndex}][question]" 
                       placeholder="Enter your screening question">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Question Type</label>
                    <select name="screening_questions[${questionIndex}][type]" onchange="toggleQuestionOptions(${questionIndex})">
                        <option value="short_answer">Short Answer</option>
                        <option value="long_answer">Long Answer</option>
                        <option value="yes_no">Yes/No</option>
                        <option value="multiple_choice">Multiple Choice</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Required</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="screening_questions[${questionIndex}][required]" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="question-options" id="questionOptions${questionIndex}" style="display: none;">
                <label>Answer Options (one per line)</label>
                <textarea name="screening_questions[${questionIndex}][options]" rows="3" 
                          placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
        </div>
    `

  container.insertAdjacentHTML("beforeend", questionHtml)
  questionIndex++

  // Update question numbers
  updateQuestionNumbers()
}

function removeScreeningQuestion(index) {
  const questionItem = document.querySelector(`[data-index="${index}"]`)
  if (questionItem) {
    questionItem.remove()
    updateQuestionNumbers()

    // Show no questions message if all removed
    const container = document.getElementById("screeningQuestions")
    if (container.children.length === 0) {
      container.innerHTML =
        '<div class="no-questions"><p>No screening questions added yet. Click "Add Question" to create your first screening question.</p></div>'
    }
  }
}

function toggleQuestionOptions(index) {
  const select = document.querySelector(`select[name="screening_questions[${index}][type]"]`)
  const optionsDiv = document.getElementById(`questionOptions${index}`)

  if (select.value === "multiple_choice") {
    optionsDiv.style.display = "block"
  } else {
    optionsDiv.style.display = "none"
  }
}

function updateQuestionNumbers() {
  const questions = document.querySelectorAll(".screening-question-item")
  questions.forEach((question, index) => {
    const numberSpan = question.querySelector(".question-number")
    numberSpan.textContent = `Question ${index + 1}`
  })
}

// Preview functionality
function previewDescription() {
  const description = document.getElementById("description").value
  const requirements = document.getElementById("requirements").value
  const responsibilities = document.getElementById("responsibilities").value
  const benefits = document.getElementById("benefits").value

  const previewContent = `
        <div class="job-preview">
            <h3>Job Description</h3>
            <div class="preview-section">
                ${description.replace(/\n/g, "<br>")}
            </div>
            
            ${
              requirements
                ? `
                <h4>Requirements</h4>
                <ul class="preview-list">
                    ${requirements
                      .split("\n")
                      .filter((req) => req.trim())
                      .map((req) => `<li>${req.trim()}</li>`)
                      .join("")}
                </ul>
            `
                : ""
            }
            
            ${
              responsibilities
                ? `
                <h4>Responsibilities</h4>
                <ul class="preview-list">
                    ${responsibilities
                      .split("\n")
                      .filter((resp) => resp.trim())
                      .map((resp) => `<li>${resp.trim()}</li>`)
                      .join("")}
                </ul>
            `
                : ""
            }
            
            ${
              benefits
                ? `
                <h4>Benefits</h4>
                <ul class="preview-list">
                    ${benefits
                      .split("\n")
                      .filter((benefit) => benefit.trim())
                      .map((benefit) => `<li>${benefit.trim()}</li>`)
                      .join("")}
                </ul>
            `
                : ""
            }
        </div>
    `

  document.getElementById("previewContent").innerHTML = previewContent
  document.getElementById("previewModal").style.display = "block"
}

function previewJob() {
  const form = document.getElementById("editJobForm")
  const formData = new FormData(form)

  // Create full job preview
  const title = formData.get("title")
  const department = formData.get("department")
  const location = formData.get("location")
  const type = formData.get("type")
  const salaryMin = formData.get("salary_min")
  const salaryMax = formData.get("salary_max")
  const deadline = formData.get("deadline")
  const description = formData.get("description")
  const requirements = formData.get("requirements")
  const responsibilities = formData.get("responsibilities")
  const benefits = formData.get("benefits")

  const salaryRange =
    salaryMin && salaryMax
      ? `$${Number.parseInt(salaryMin).toLocaleString()} - $${Number.parseInt(salaryMax).toLocaleString()}`
      : salaryMin
        ? `From $${Number.parseInt(salaryMin).toLocaleString()}`
        : salaryMax
          ? `Up to $${Number.parseInt(salaryMax).toLocaleString()}`
          : "Competitive"

  const previewContent = `
        <div class="job-preview-full">
            <div class="job-header">
                <h2>${title || "Job Title"}</h2>
                <div class="job-meta">
                    <span class="meta-item"><i class="fas fa-building"></i> ${department || "Department"}</span>
                    <span class="meta-item"><i class="fas fa-map-marker-alt"></i> ${location || "Location"}</span>
                    <span class="meta-item"><i class="fas fa-clock"></i> ${type || "Employment Type"}</span>
                    <span class="meta-item"><i class="fas fa-dollar-sign"></i> ${salaryRange}</span>
                    <span class="meta-item"><i class="fas fa-calendar"></i> Apply by ${deadline ? new Date(deadline).toLocaleDateString() : "TBD"}</span>
                </div>
            </div>
            
            <div class="job-content">
                <h3>About This Role</h3>
                <div class="job-description">
                    ${description ? description.replace(/\n/g, "<br>") : "No description provided"}
                </div>
                
                ${
                  requirements
                    ? `
                    <h3>Requirements</h3>
                    <ul class="job-list">
                        ${requirements
                          .split("\n")
                          .filter((req) => req.trim())
                          .map((req) => `<li>${req.trim()}</li>`)
                          .join("")}
                    </ul>
                `
                    : ""
                }
                
                ${
                  responsibilities
                    ? `
                    <h3>Key Responsibilities</h3>
                    <ul class="job-list">
                        ${responsibilities
                          .split("\n")
                          .filter((resp) => resp.trim())
                          .map((resp) => `<li>${resp.trim()}</li>`)
                          .join("")}
                    </ul>
                `
                    : ""
                }
                
                ${
                  benefits
                    ? `
                    <h3>What We Offer</h3>
                    <ul class="job-list">
                        ${benefits
                          .split("\n")
                          .filter((benefit) => benefit.trim())
                          .map((benefit) => `<li>${benefit.trim()}</li>`)
                          .join("")}
                    </ul>
                `
                    : ""
                }
            </div>
        </div>
    `

  document.getElementById("previewContent").innerHTML = previewContent
  document.getElementById("previewModal").style.display = "block"
}

function closePreviewModal() {
  document.getElementById("previewModal").style.display = "none"
}

function openPublicPreview() {
  const jobId = new URLSearchParams(window.location.search).get("id")
  window.open(`../job-details.php?id=${jobId}`, "_blank")
}

// Quick actions
function duplicateJob() {
  if (confirm("This will create a copy of this job. Continue?")) {
    const form = document.getElementById("editJobForm")
    const formData = new FormData(form)

    // Modify title to indicate it's a copy
    const title = formData.get("title")
    formData.set("title", `${title} (Copy)`)

    // Reset deadline to 30 days from now
    const newDeadline = new Date()
    newDeadline.setDate(newDeadline.getDate() + 30)
    formData.set("deadline", newDeadline.toISOString().split("T")[0])

    // Set status to draft
    formData.set("status", "draft")

    // Submit to create job endpoint
    fetch("../admin/create-job.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((data) => {
        showNotification("Job duplicated successfully!", "success")
        setTimeout(() => {
          window.location.href = "dashboard.php"
        }, 1500)
      })
      .catch((error) => {
        console.error("Error:", error)
        showNotification("Failed to duplicate job", "error")
      })
  }
}

function extendDeadline() {
  const currentDeadline = document.getElementById("deadline").value
  const days = prompt("Extend deadline by how many days?", "30")

  if (days && Number.parseInt(days) > 0) {
    const newDeadline = new Date(currentDeadline)
    newDeadline.setDate(newDeadline.getDate() + Number.parseInt(days))

    document.getElementById("deadline").value = newDeadline.toISOString().split("T")[0]
    showNotification(`Deadline extended by ${days} days`, "success")

    // Trigger auto-save
    autoSaveJob()
  }
}

function saveDraft() {
  const statusField = document.getElementById("status")
  const originalStatus = statusField.value

  // Temporarily set status to draft
  statusField.value = "draft"

  // Submit form
  const form = document.getElementById("editJobForm")
  const formData = new FormData(form)

  fetch(window.location.href, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.text())
    .then((data) => {
      showNotification("Job saved as draft", "success")
      // Clear localStorage draft
      localStorage.removeItem("job_edit_draft")
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("Failed to save draft", "error")
      // Restore original status
      statusField.value = originalStatus
    })
}

// Utility functions
function showNotification(message, type = "info") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification")
  existingNotifications.forEach((notification) => notification.remove())

  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 0.375rem;
        color: white;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `

  // Set background color based on type
  switch (type) {
    case "success":
      notification.style.backgroundColor = "#10b981"
      break
    case "error":
      notification.style.backgroundColor = "#dc2626"
      break
    case "warning":
      notification.style.backgroundColor = "#f59e0b"
      break
    default:
      notification.style.backgroundColor = "#3b82f6"
  }

  notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; padding: 0; margin-left: 0.5rem;">
                &times;
            </button>
        </div>
    `

  document.body.appendChild(notification)

  // Animate in
  setTimeout(() => {
    notification.style.transform = "translateX(0)"
  }, 100)

  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.style.transform = "translateX(100%)"
    setTimeout(() => {
      notification.remove()
    }, 300)
  }, 5000)
}

// Export functions to global scope
window.addScreeningQuestion = addScreeningQuestion
window.removeScreeningQuestion = removeScreeningQuestion
window.toggleQuestionOptions = toggleQuestionOptions
window.previewDescription = previewDescription
window.previewJob = previewJob
window.closePreviewModal = closePreviewModal
window.openPublicPreview = openPublicPreview
window.duplicateJob = duplicateJob
window.extendDeadline = extendDeadline
window.saveDraft = saveDraft
