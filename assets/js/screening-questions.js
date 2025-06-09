// Screening Questions Module
let questionIndex = 0
let autoSaveTimeout

// Question templates data
const questionTemplatesData = {
  general: {
    experience: {
      question: "How many years of relevant experience do you have?",
      type: "multiple_choice",
      options: ["Less than 1 year", "1-2 years", "3-5 years", "5-10 years", "More than 10 years"],
      required: true,
    },
    availability: {
      question: "When would you be available to start?",
      type: "multiple_choice",
      options: ["Immediately", "Within 2 weeks", "Within 1 month", "Within 2 months", "More than 2 months"],
      required: true,
    },
    salary: {
      question: "What are your salary expectations for this role?",
      type: "short_answer",
      required: false,
    },
    relocation: {
      question: "Are you willing to relocate for this position?",
      type: "yes_no",
      required: true,
    },
  },
  technical: {
    programming: {
      question: "Which programming languages are you proficient in?",
      type: "long_answer",
      required: true,
    },
    frameworks: {
      question: "What frameworks and tools have you worked with?",
      type: "long_answer",
      required: true,
    },
    portfolio: {
      question: "Please provide a link to your portfolio or GitHub profile",
      type: "short_answer",
      required: false,
    },
    certifications: {
      question: "Do you have any relevant certifications?",
      type: "long_answer",
      required: false,
    },
  },
  role: {
    leadership: {
      question: "Do you have experience leading teams or projects?",
      type: "yes_no",
      required: false,
    },
    remote: {
      question: "Do you have experience working remotely?",
      type: "multiple_choice",
      options: ["No experience", "Some experience", "Extensive experience", "Prefer remote work"],
      required: false,
    },
    travel: {
      question: "Are you comfortable with travel requirements (up to 25%)?",
      type: "yes_no",
      required: true,
    },
    motivation: {
      question: "What motivates you to apply for this position?",
      type: "long_answer",
      required: true,
    },
  },
}

// Initialize screening questions module
function initializeScreeningQuestions() {
  // Set up event listeners
  setupQuestionEventListeners()

  // Load any existing questions from form data or localStorage
  loadExistingQuestions()
}

function setupQuestionEventListeners() {
  // Department change listener
  const departmentSelect = document.getElementById("department")
  if (departmentSelect) {
    departmentSelect.addEventListener("change", loadDepartmentQuestions)
  }

  // Type change listener
  const typeSelect = document.getElementById("type")
  if (typeSelect) {
    typeSelect.addEventListener("change", loadTypeQuestions)
  }
}

function loadExistingQuestions() {
  // Check if there are any existing questions in the form
  const existingQuestions = document.querySelectorAll(".screening-question-item")
  if (existingQuestions.length > 0) {
    questionIndex = existingQuestions.length
    hideNoQuestionsMessage()
  }
}

// Add a new screening question
function addScreeningQuestion(templateData = null) {
  const container = document.getElementById("screeningQuestions")
  const noQuestions = container.querySelector(".no-questions")

  if (noQuestions) {
    noQuestions.style.display = "none"
  }

  const questionData = templateData || {
    question: "",
    type: "short_answer",
    options: [],
    required: false,
  }

  const questionHtml = createQuestionHTML(questionIndex, questionData)
  container.insertAdjacentHTML("beforeend", questionHtml)

  // Initialize the new question
  initializeQuestion(questionIndex)

  questionIndex++
  updateQuestionNumbers()

  // Scroll to the new question
  const newQuestion = container.lastElementChild
  newQuestion.scrollIntoView({ behavior: "smooth", block: "center" })

  // Focus on the question input
  const questionInput = newQuestion.querySelector('input[name*="[question]"]')
  if (questionInput) {
    questionInput.focus()
  }

  // Trigger auto-save
  triggerAutoSave()
}

function createQuestionHTML(index, data) {
  const optionsDisplay = data.type === "multiple_choice" ? "block" : "none"
  const optionsValue = Array.isArray(data.options) ? data.options.join("\n") : ""

  return `
        <div class="screening-question-item" data-index="${index}">
            <div class="question-header">
                <div class="question-header-left">
                    <span class="question-number">Question ${index + 1}</span>
                    <div class="question-type-indicator">
                        <span class="type-badge type-${data.type}">${getTypeLabel(data.type)}</span>
                    </div>
                </div>
                <div class="question-header-actions">
                    <button type="button" class="btn btn-small btn-outline" onclick="duplicateQuestion(${index})" title="Duplicate">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-small btn-outline" onclick="moveQuestionUp(${index})" title="Move Up">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-small btn-outline" onclick="moveQuestionDown(${index})" title="Move Down">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-small btn-outline btn-danger" onclick="removeScreeningQuestion(${index})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="question-content">
                <div class="form-group">
                    <label>Question Text *</label>
                    <input type="text" name="screening_questions[${index}][question]" 
                           value="${escapeHtml(data.question)}"
                           placeholder="Enter your screening question"
                           onchange="updateQuestionPreview(${index})"
                           required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Question Type</label>
                        <select name="screening_questions[${index}][type]" onchange="toggleQuestionOptions(${index})">
                            <option value="short_answer" ${data.type === "short_answer" ? "selected" : ""}>Short Answer</option>
                            <option value="long_answer" ${data.type === "long_answer" ? "selected" : ""}>Long Answer</option>
                            <option value="yes_no" ${data.type === "yes_no" ? "selected" : ""}>Yes/No</option>
                            <option value="multiple_choice" ${data.type === "multiple_choice" ? "selected" : ""}>Multiple Choice</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Required</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="screening_questions[${index}][required]" 
                                   ${data.required ? "checked" : ""}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="question-options" id="questionOptions${index}" style="display: ${optionsDisplay};">
                    <label>Answer Options (one per line)</label>
                    <textarea name="screening_questions[${index}][options]" rows="4" 
                              placeholder="Option 1&#10;Option 2&#10;Option 3&#10;Option 4"
                              onchange="updateQuestionPreview(${index})">${optionsValue}</textarea>
                    <small class="form-help">Enter each option on a new line. You can add as many options as needed.</small>
                </div>
                
                <div class="question-help">
                    <label>Help Text (optional)</label>
                    <input type="text" name="screening_questions[${index}][help]" 
                           placeholder="Additional instructions or context for this question"
                           value="${escapeHtml(data.help || "")}">
                </div>
            </div>
        </div>
    `
}

function initializeQuestion(index) {
  // Set up any specific initialization for the question
  const questionElement = document.querySelector(`[data-index="${index}"]`)
  if (questionElement) {
    // Add any specific event listeners or initialization
    const typeSelect = questionElement.querySelector('select[name*="[type]"]')
    if (typeSelect) {
      toggleQuestionOptions(index)
    }
  }
}

// Remove a screening question
function removeScreeningQuestion(index) {
  if (confirm("Are you sure you want to delete this question?")) {
    const questionItem = document.querySelector(`[data-index="${index}"]`)
    if (questionItem) {
      questionItem.remove()
      updateQuestionNumbers()

      // Show no questions message if all removed
      const container = document.getElementById("screeningQuestions")
      const remainingQuestions = container.querySelectorAll(".screening-question-item")
      if (remainingQuestions.length === 0) {
        showNoQuestionsMessage()
      }

      triggerAutoSave()
    }
  }
}

// Duplicate a question
function duplicateQuestion(index) {
  const questionItem = document.querySelector(`[data-index="${index}"]`)
  if (questionItem) {
    // Get the question data
    const questionInput = questionItem.querySelector('input[name*="[question]"]')
    const typeSelect = questionItem.querySelector('select[name*="[type]"]')
    const requiredCheckbox = questionItem.querySelector('input[name*="[required]"]')
    const optionsTextarea = questionItem.querySelector('textarea[name*="[options]"]')
    const helpInput = questionItem.querySelector('input[name*="[help]"]')

    const questionData = {
      question: questionInput.value + " (Copy)",
      type: typeSelect.value,
      required: requiredCheckbox.checked,
      options: optionsTextarea ? optionsTextarea.value.split("\n").filter((opt) => opt.trim()) : [],
      help: helpInput ? helpInput.value : "",
    }

    addScreeningQuestion(questionData)
  }
}

// Move question up
function moveQuestionUp(index) {
  const questionItem = document.querySelector(`[data-index="${index}"]`)
  const prevQuestion = questionItem.previousElementSibling

  if (prevQuestion && prevQuestion.classList.contains("screening-question-item")) {
    questionItem.parentNode.insertBefore(questionItem, prevQuestion)
    updateQuestionNumbers()
    triggerAutoSave()
  }
}

// Move question down
function moveQuestionDown(index) {
  const questionItem = document.querySelector(`[data-index="${index}"]`)
  const nextQuestion = questionItem.nextElementSibling

  if (nextQuestion && nextQuestion.classList.contains("screening-question-item")) {
    questionItem.parentNode.insertBefore(nextQuestion, questionItem)
    updateQuestionNumbers()
    triggerAutoSave()
  }
}

// Toggle question options visibility
function toggleQuestionOptions(index) {
  const select = document.querySelector(`select[name="screening_questions[${index}][type]"]`)
  const optionsDiv = document.getElementById(`questionOptions${index}`)
  const typeBadge = document.querySelector(`[data-index="${index}"] .type-badge`)

  if (select && optionsDiv) {
    if (select.value === "multiple_choice") {
      optionsDiv.style.display = "block"
    } else {
      optionsDiv.style.display = "none"
    }

    // Update type badge
    if (typeBadge) {
      typeBadge.textContent = getTypeLabel(select.value)
      typeBadge.className = `type-badge type-${select.value}`
    }

    triggerAutoSave()
  }
}

// Update question numbers
function updateQuestionNumbers() {
  const questions = document.querySelectorAll(".screening-question-item")
  questions.forEach((question, index) => {
    const numberSpan = question.querySelector(".question-number")
    if (numberSpan) {
      numberSpan.textContent = `Question ${index + 1}`
    }

    // Update data-index
    question.setAttribute("data-index", index)

    // Update form field names
    updateFieldNames(question, index)
  })
}

function updateFieldNames(questionElement, newIndex) {
  const inputs = questionElement.querySelectorAll("input, select, textarea")
  inputs.forEach((input) => {
    if (input.name && input.name.includes("screening_questions[")) {
      const fieldName = input.name.replace(/screening_questions\[\d+\]/, `screening_questions[${newIndex}]`)
      input.name = fieldName
    }
  })

  // Update onclick handlers
  const buttons = questionElement.querySelectorAll("button[onclick]")
  buttons.forEach((button) => {
    if (button.onclick) {
      const onclickStr = button.onclick.toString()
      const newOnclick = onclickStr.replace(/$$\d+$$/, `(${newIndex})`)
      button.setAttribute("onclick", newOnclick.match(/\{(.*)\}/)[1])
    }
  })

  // Update IDs
  const optionsDiv = questionElement.querySelector(".question-options")
  if (optionsDiv) {
    optionsDiv.id = `questionOptions${newIndex}`
  }
}

// Show/hide question templates
function showQuestionTemplates() {
  document.getElementById("questionTemplatesPanel").style.display = "block"
}

function hideQuestionTemplates() {
  document.getElementById("questionTemplatesPanel").style.display = "none"
}

// Add template question
function addTemplateQuestion(category, type) {
  const templateData = questionTemplatesData[category][type]
  if (templateData) {
    addScreeningQuestion(templateData)
    hideQuestionTemplates()
  }
}

// Load department-specific questions
function loadDepartmentQuestions() {
  const department = document.getElementById("department").value
  if (!department) return

  // Show suggestion to add department-specific questions
  showQuestionSuggestions(department)
}

// Load type-specific questions
function loadTypeQuestions() {
  const type = document.getElementById("type").value
  if (!type) return

  // Show suggestion to add type-specific questions
  showQuestionSuggestions(null, type)
}

function showQuestionSuggestions(department, type) {
  // This could show a notification or modal with suggested questions
  // For now, we'll just log it
  console.log("Suggestions for:", { department, type })
}

// Preview questions
function previewQuestions() {
  const questions = collectQuestionData()
  if (questions.length === 0) {
    alert("No questions to preview. Add some questions first.")
    return
  }

  const previewContent = generateQuestionPreview(questions)
  document.getElementById("previewContent").innerHTML = previewContent
  document.getElementById("questionPreview").style.display = "block"

  // Scroll to preview
  document.getElementById("questionPreview").scrollIntoView({ behavior: "smooth" })
}

function hideQuestionPreview() {
  document.getElementById("questionPreview").style.display = "none"
}

function generateQuestionPreview(questions) {
  let html = '<div class="application-form-preview">'
  html += "<h3>Screening Questions</h3>"
  html += '<p class="preview-note">This is how the questions will appear to applicants:</p>'

  questions.forEach((question, index) => {
    html += `<div class="preview-question">`
    html += `<label class="preview-label">`
    html += `${index + 1}. ${escapeHtml(question.question)}`
    if (question.required) {
      html += ' <span class="required">*</span>'
    }
    html += `</label>`

    if (question.help) {
      html += `<small class="preview-help">${escapeHtml(question.help)}</small>`
    }

    // Generate appropriate input based on type
    switch (question.type) {
      case "short_answer":
        html += '<input type="text" class="preview-input" placeholder="Your answer..." disabled>'
        break
      case "long_answer":
        html += '<textarea class="preview-textarea" rows="4" placeholder="Your detailed answer..." disabled></textarea>'
        break
      case "yes_no":
        html += `
                    <div class="preview-radio-group">
                        <label><input type="radio" name="preview_${index}" disabled> Yes</label>
                        <label><input type="radio" name="preview_${index}" disabled> No</label>
                    </div>
                `
        break
      case "multiple_choice":
        html += '<div class="preview-radio-group">'
        if (question.options && question.options.length > 0) {
          question.options.forEach((option) => {
            html += `<label><input type="radio" name="preview_${index}" disabled> ${escapeHtml(option)}</label>`
          })
        } else {
          html += '<p class="preview-note">No options defined</p>'
        }
        html += "</div>"
        break
    }

    html += "</div>"
  })

  html += "</div>"
  return html
}

function collectQuestionData() {
  const questions = []
  const questionElements = document.querySelectorAll(".screening-question-item")

  questionElements.forEach((element) => {
    const questionInput = element.querySelector('input[name*="[question]"]')
    const typeSelect = element.querySelector('select[name*="[type]"]')
    const requiredCheckbox = element.querySelector('input[name*="[required]"]')
    const optionsTextarea = element.querySelector('textarea[name*="[options]"]')
    const helpInput = element.querySelector('input[name*="[help]"]')

    if (questionInput && questionInput.value.trim()) {
      const question = {
        question: questionInput.value.trim(),
        type: typeSelect.value,
        required: requiredCheckbox.checked,
        help: helpInput ? helpInput.value.trim() : "",
      }

      if (typeSelect.value === "multiple_choice" && optionsTextarea) {
        question.options = optionsTextarea.value
          .split("\n")
          .map((opt) => opt.trim())
          .filter((opt) => opt.length > 0)
      }

      questions.push(question)
    }
  })

  return questions
}

// Auto-save functionality
function setupAutoSave() {
  const form = document.getElementById("createJobForm")
  const inputs = form.querySelectorAll("input, select, textarea")

  inputs.forEach((input) => {
    input.addEventListener("input", triggerAutoSave)
    input.addEventListener("change", triggerAutoSave)
  })
}

function triggerAutoSave() {
  clearTimeout(autoSaveTimeout)
  autoSaveTimeout = setTimeout(autoSaveJob, 2000)
}

function autoSaveJob() {
  const indicator = document.getElementById("autoSaveIndicator")
  indicator.style.display = "block"

  // Save to localStorage
  const formData = new FormData(document.getElementById("createJobForm"))
  const draftData = {}

  for (const [key, value] of formData.entries()) {
    draftData[key] = value
  }

  // Also save questions data
  draftData.screening_questions = collectQuestionData()

  localStorage.setItem("job_create_draft", JSON.stringify(draftData))

  setTimeout(() => {
    indicator.style.display = "none"
  }, 2000)
}

// Utility functions
function showNoQuestionsMessage() {
  const container = document.getElementById("screeningQuestions")
  const noQuestions = container.querySelector(".no-questions")
  if (noQuestions) {
    noQuestions.style.display = "block"
  }
}

function hideNoQuestionsMessage() {
  const container = document.getElementById("screeningQuestions")
  const noQuestions = container.querySelector(".no-questions")
  if (noQuestions) {
    noQuestions.style.display = "none"
  }
}

function getTypeLabel(type) {
  const labels = {
    short_answer: "Short Answer",
    long_answer: "Long Answer",
    yes_no: "Yes/No",
    multiple_choice: "Multiple Choice",
  }
  return labels[type] || type
}

function escapeHtml(text) {
  const div = document.createElement("div")
  div.textContent = text
  return div.innerHTML
}

function updateQuestionPreview(index) {
  // Update the live preview if it's open
  if (document.getElementById("questionPreview").style.display === "block") {
    previewQuestions()
  }
}

// Export functions to global scope
window.addScreeningQuestion = addScreeningQuestion
window.removeScreeningQuestion = removeScreeningQuestion
window.duplicateQuestion = duplicateQuestion
window.moveQuestionUp = moveQuestionUp
window.moveQuestionDown = moveQuestionDown
window.toggleQuestionOptions = toggleQuestionOptions
window.showQuestionTemplates = showQuestionTemplates
window.hideQuestionTemplates = hideQuestionTemplates
window.addTemplateQuestion = addTemplateQuestion
window.loadDepartmentQuestions = loadDepartmentQuestions
window.loadTypeQuestions = loadTypeQuestions
window.previewQuestions = previewQuestions
window.hideQuestionPreview = hideQuestionPreview
window.updateQuestionPreview = updateQuestionPreview
