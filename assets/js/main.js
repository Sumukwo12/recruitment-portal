// Admin dashboard JavaScript functionality

// Tab switching
function showTab(tabName) {
  // Hide all tab contents
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.classList.remove("active")
  })

  // Remove active class from all tab buttons
  document.querySelectorAll(".tab-button").forEach((button) => {
    button.classList.remove("active")
  })

  // Show selected tab content
  const selectedTab = document.getElementById(`${tabName}-tab`)
  if (selectedTab) {
    selectedTab.classList.add("active")
  }

  // Add active class to selected tab button
  const selectedButton = document.querySelector(`[onclick="showTab('${tabName}')"]`)
  if (selectedButton) {
    selectedButton.classList.add("active")
  }
}

// Job filtering
function filterJobs() {
  const searchTerm = document.getElementById("jobSearch")?.value.toLowerCase() || ""
  const statusFilter = document.getElementById("statusFilter")?.value || ""
  const departmentFilter = document.getElementById("departmentFilter")?.value || ""

  const rows = document.querySelectorAll("#jobsTableBody tr")

  rows.forEach((row) => {
    const title = row.querySelector(".job-title")?.textContent.toLowerCase() || ""
    const department = row.dataset.department || ""
    const status = row.dataset.status || ""

    const matchesSearch = title.includes(searchTerm) || department.toLowerCase().includes(searchTerm)
    const matchesStatus = !statusFilter || status === statusFilter
    const matchesDepartment = !departmentFilter || department === departmentFilter

    if (matchesSearch && matchesStatus && matchesDepartment) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })
}

// Advanced filters toggle
function toggleAdvancedFilters() {
  const filtersPanel = document.getElementById("advancedFilters")
  if (filtersPanel.style.display === "none" || !filtersPanel.style.display) {
    filtersPanel.style.display = "block"
  } else {
    filtersPanel.style.display = "none"
  }
}

// Quick filter application
function applyQuickFilter(filterType, value) {
  const url = new URL(window.location)
  url.searchParams.set(filterType, value)
  url.searchParams.set("tab", "applications")
  window.location.href = url.toString()
}

// Bulk actions functionality
function toggleSelectAll() {
  const selectAllCheckbox = document.getElementById("selectAll")
  const applicationCheckboxes = document.querySelectorAll(".application-checkbox")

  applicationCheckboxes.forEach((checkbox) => {
    checkbox.checked = selectAllCheckbox.checked
  })

  updateBulkActions()
}

function updateBulkActions() {
  const selectedCheckboxes = document.querySelectorAll(".application-checkbox:checked")
  const bulkActionsBtn = document.getElementById("bulkActionsBtn")
  const bulkActionsPanel = document.getElementById("bulkActionsPanel")
  const selectedCount = document.querySelector(".selected-count")

  if (selectedCheckboxes.length > 0) {
    bulkActionsBtn.style.display = "inline-flex"
    bulkActionsPanel.style.display = "block"
    selectedCount.textContent = `${selectedCheckboxes.length} applications selected`
  } else {
    bulkActionsBtn.style.display = "none"
    bulkActionsPanel.style.display = "none"
  }
}

function showBulkActions() {
  const bulkActionsPanel = document.getElementById("bulkActionsPanel")
  bulkActionsPanel.style.display = bulkActionsPanel.style.display === "none" ? "block" : "none"
}

function applyBulkAction() {
  const selectedCheckboxes = document.querySelectorAll(".application-checkbox:checked")
  const bulkStatus = document.getElementById("bulkStatusSelect").value

  if (!bulkStatus) {
    showNotification("Please select a status to apply", "error")
    return
  }

  if (selectedCheckboxes.length === 0) {
    showNotification("Please select applications to update", "error")
    return
  }

  const applicationIds = Array.from(selectedCheckboxes).map((cb) => cb.value)

  if (!confirm(`Are you sure you want to update ${applicationIds.length} applications to "${bulkStatus}" status?`)) {
    return
  }

  fetch("dashboard.php?action=bulk_update_status", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `application_ids=${JSON.stringify(applicationIds)}&status=${bulkStatus}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(`Successfully updated ${data.updated} applications`, "success")
        setTimeout(() => location.reload(), 1000)
      } else {
        showNotification("Failed to update applications", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

function clearSelection() {
  const checkboxes = document.querySelectorAll(".application-checkbox, #selectAll")
  checkboxes.forEach((cb) => (cb.checked = false))
  updateBulkActions()
}

// Export functionality
function exportApplications() {
  const url = new URL(window.location)
  const params = new URLSearchParams(url.search)

  fetch(`dashboard.php?action=export_applications&${params.toString()}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        downloadCSV(data.data, "applications_export.csv")
        showNotification("Applications exported successfully", "success")
      } else {
        showNotification("Failed to export applications", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred during export", "error")
    })
}

function exportSelected() {
  const selectedCheckboxes = document.querySelectorAll(".application-checkbox:checked")

  if (selectedCheckboxes.length === 0) {
    showNotification("Please select applications to export", "error")
    return
  }

  const applicationIds = Array.from(selectedCheckboxes).map((cb) => cb.value)

  // Create export data from selected rows
  const exportData = []
  const headers = ["Name", "Email", "Phone", "Position", "Department", "Applied Date", "Status"]
  exportData.push(headers)

  selectedCheckboxes.forEach((checkbox) => {
    const row = checkbox.closest("tr")
    const name = row.querySelector(".applicant-name").textContent.trim()
    const email = row.querySelector(".applicant-email").textContent.trim()
    const phone = row.querySelector(".applicant-phone").textContent.trim()
    const position = row.querySelector(".job-title").textContent.trim()
    const department = row.querySelector(".job-department").textContent.trim()
    const appliedDate = row.querySelector(".applied-date").textContent.trim()
    const status = row.querySelector(".status-select").value

    exportData.push([name, email, phone, position, department, appliedDate, status])
  })

  downloadCSV(exportData, "selected_applications.csv")
  showNotification(`Exported ${selectedCheckboxes.length} selected applications`, "success")
}

function downloadCSV(data, filename) {
  const csvContent = data.map((row) => row.map((cell) => `"${cell || ""}"`).join(",")).join("\n")

  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
  const link = document.createElement("a")
  const url = URL.createObjectURL(blob)
  link.setAttribute("href", url)
  link.setAttribute("download", filename)
  link.style.visibility = "hidden"
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Table sorting
function sortTable(column) {
  const table = document.querySelector(".data-table")
  const tbody = table.querySelector("tbody")
  const rows = Array.from(tbody.querySelectorAll("tr"))

  // Determine sort direction
  const currentSort = table.dataset.sortColumn
  const currentDirection = table.dataset.sortDirection || "asc"
  const newDirection = currentSort === column && currentDirection === "asc" ? "desc" : "asc"

  // Sort rows
  rows.sort((a, b) => {
    let aValue, bValue

    switch (column) {
      case "applicant":
        aValue = a.querySelector(".applicant-name").textContent.trim()
        bValue = b.querySelector(".applicant-name").textContent.trim()
        break
      case "position":
        aValue = a.querySelector(".job-title").textContent.trim()
        bValue = b.querySelector(".job-title").textContent.trim()
        break
      case "applied_date":
        aValue = new Date(a.querySelector(".applied-date").textContent.trim())
        bValue = new Date(b.querySelector(".applied-date").textContent.trim())
        break
      default:
        return 0
    }

    if (aValue < bValue) return newDirection === "asc" ? -1 : 1
    if (aValue > bValue) return newDirection === "asc" ? 1 : -1
    return 0
  })

  // Update table
  rows.forEach((row) => tbody.appendChild(row))

  // Update sort indicators
  table.dataset.sortColumn = column
  table.dataset.sortDirection = newDirection

  // Update sort icons
  document.querySelectorAll(".sortable i").forEach((icon) => {
    icon.className = "fas fa-sort"
  })

  const activeIcon = document.querySelector(`[onclick="sortTable('${column}')"] i`)
  if (activeIcon) {
    activeIcon.className = `fas fa-sort-${newDirection === "asc" ? "up" : "down"}`
  }
}

// Filter presets
function saveFilterPreset() {
  const form = document.querySelector(".filters-form")
  const formData = new FormData(form)
  const preset = {}

  for (const [key, value] of formData.entries()) {
    if (value && key !== "tab") {
      preset[key] = value
    }
  }

  const presetName = prompt("Enter a name for this filter preset:")
  if (presetName) {
    localStorage.setItem(`filter_preset_${presetName}`, JSON.stringify(preset))
    showNotification(`Filter preset "${presetName}" saved`, "success")
  }
}

// Application actions
function scheduleInterview(applicationId) {
  // This would integrate with a calendar system
  showNotification("Interview scheduling feature coming soon!", "info")
}

function sendEmail(email, jobTitle) {
  const subject = encodeURIComponent(`Re: Your application for ${jobTitle}`)
  const mailtoLink = `mailto:${email}?subject=${subject}`
  window.open(mailtoLink)
}

// Delete job
function deleteJob(jobId) {
  if (!confirm("Are you sure you want to delete this job? This action cannot be undone.")) {
    return
  }

  fetch("dashboard.php?action=delete_job", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `job_id=${jobId}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("Job deleted successfully", "success")
        location.reload()
      } else {
        showNotification("Failed to delete job", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

// Update application status
function updateApplicationStatus(applicationId, status) {
  fetch("dashboard.php?action=update_application_status", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `application_id=${applicationId}&status=${status}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("Application status updated", "success")

        // Update the row styling based on new status
        const select = document.querySelector(`select[onchange*="${applicationId}"]`)
        if (select) {
          select.dataset.originalStatus = status
        }
      } else {
        showNotification("Failed to update status", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

// Show notification
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

// Settings functionality
function toggleJobBoardVisibility() {
  fetch("dashboard.php?action=toggle_job_board", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const statusElement = document.getElementById("jobBoardStatus")
        const button = document.querySelector('[onclick="toggleJobBoardVisibility()"]')

        if (data.status === "1") {
          statusElement.textContent = "Visible"
          statusElement.className = "badge badge-success"
          button.innerHTML = '<i class="fas fa-toggle-on"></i> Hide Job Board'
        } else {
          statusElement.textContent = "Hidden"
          statusElement.className = "badge badge-danger"
          button.innerHTML = '<i class="fas fa-toggle-off"></i> Show Job Board'
        }

        showNotification(data.message, "success")
      } else {
        showNotification("Failed to update job board visibility", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

function updateNotificationSettings(event) {
  event.preventDefault()

  const formData = new FormData(event.target)
  const emailNotifications = formData.get("email_notifications") ? "1" : "0"
  const notificationEmail = formData.get("notification_email")

  fetch("dashboard.php?action=update_notification_settings", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `email_notifications=${emailNotifications}&notification_email=${encodeURIComponent(notificationEmail)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success")
      } else {
        showNotification("Failed to update notification settings", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

function testNotification() {
  showNotification("Test email sent! (Feature coming soon)", "info")
}

function updateCVFilters(event) {
  event.preventDefault()

  const formData = new FormData(event.target)
  const cvSections = formData.getAll("cv_sections[]")

  fetch("dashboard.php?action=update_cv_filters", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `cv_sections=${JSON.stringify(cvSections)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success")
      } else {
        showNotification("Failed to update CV filter settings", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

function resetCVFilters() {
  const checkboxes = document.querySelectorAll('input[name="cv_sections[]"]')
  checkboxes.forEach((checkbox) => (checkbox.checked = true))
  showNotification("CV filters reset to default", "info")
}

function showBulkDeadlineExtension() {
  const modal = document.getElementById("bulkDeadlineModal")
  const jobList = document.getElementById("jobSelectionList")

  // Populate job list
  const jobs = document.querySelectorAll("#jobsTableBody tr")
  jobList.innerHTML = ""

  jobs.forEach((row, index) => {
    const jobTitle = row.querySelector(".job-title").textContent
    const jobId = row.querySelector(".job-checkbox").value
    const deadline = row.cells[5].textContent

    const jobItem = document.createElement("div")
    jobItem.className = "job-selection-item"
    jobItem.innerHTML = `
      <label>
        <input type="checkbox" value="${jobId}" class="bulk-job-checkbox">
        <span class="job-info">
          <strong>${jobTitle}</strong>
          <small>Current deadline: ${deadline}</small>
        </span>
      </label>
    `
    jobList.appendChild(jobItem)
  })

  modal.style.display = "block"
}

function closeBulkDeadlineModal() {
  document.getElementById("bulkDeadlineModal").style.display = "none"
}

function confirmBulkExtendDeadlines() {
  const selectedJobs = document.querySelectorAll(".bulk-job-checkbox:checked")
  const extendDays = document.getElementById("modalExtendDays").value

  if (selectedJobs.length === 0) {
    showNotification("Please select at least one job", "error")
    return
  }

  if (!extendDays || extendDays < 1) {
    showNotification("Please enter a valid number of days", "error")
    return
  }

  const jobIds = Array.from(selectedJobs).map((cb) => cb.value)

  fetch("dashboard.php?action=bulk_extend_deadlines", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `job_ids=${JSON.stringify(jobIds)}&extend_days=${extendDays}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success")
        closeBulkDeadlineModal()
        setTimeout(() => location.reload(), 1000)
      } else {
        showNotification("Failed to extend deadlines", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

function exportAllData() {
  showNotification("Preparing data export...", "info")

  // Create a comprehensive export
  const exportData = {
    jobs: [],
    applications: [],
  }

  // Export jobs
  const jobRows = document.querySelectorAll("#jobsTableBody tr")
  jobRows.forEach((row) => {
    const cells = row.querySelectorAll("td")
    exportData.jobs.push({
      title: cells[1].textContent.trim(),
      department: cells[2].textContent.trim(),
      status: cells[3].textContent.trim(),
      applications: cells[4].textContent.trim(),
      deadline: cells[5].textContent.trim(),
    })
  })

  // Export applications
  const appRows = document.querySelectorAll("#applicationsTableBody tr")
  appRows.forEach((row) => {
    const cells = row.querySelectorAll("td")
    if (cells.length > 1) {
      exportData.applications.push({
        name: cells[1].querySelector(".applicant-name")?.textContent.trim(),
        email: cells[1].querySelector(".applicant-email")?.textContent.trim(),
        position: cells[2].querySelector(".job-title")?.textContent.trim(),
        applied_date: cells[3].querySelector(".applied-date")?.textContent.trim(),
        status: cells[4].querySelector("select")?.value,
      })
    }
  })

  // Download as JSON
  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: "application/json" })
  const link = document.createElement("a")
  const url = URL.createObjectURL(blob)
  link.setAttribute("href", url)
  link.setAttribute("download", `system_export_${new Date().toISOString().split("T")[0]}.json`)
  link.style.visibility = "hidden"
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  showNotification("System data exported successfully", "success")
}

function createSystemBackup() {
  showNotification("Creating system backup... (Feature coming soon)", "info")
}

function showCleanupOptions() {
  const days = prompt("Archive applications older than how many days?", "90")
  if (days && Number.parseInt(days) > 0) {
    if (confirm(`This will archive applications older than ${days} days. Continue?`)) {
      showNotification(`Cleanup scheduled for applications older than ${days} days (Feature coming soon)`, "info")
    }
  }
}

// Job bulk operations
function toggleSelectAllJobs() {
  const selectAllCheckbox = document.getElementById("selectAllJobs")
  const jobCheckboxes = document.querySelectorAll(".job-checkbox")

  jobCheckboxes.forEach((checkbox) => {
    checkbox.checked = selectAllCheckbox.checked
  })

  updateJobBulkActions()
}

function updateJobBulkActions() {
  const selectedCheckboxes = document.querySelectorAll(".job-checkbox:checked")
  const bulkActionsPanel = document.getElementById("jobBulkActionsPanel")
  const selectedCount = document.getElementById("jobSelectedCount")

  if (selectedCheckboxes.length > 0) {
    bulkActionsPanel.style.display = "block"
    selectedCount.textContent = `${selectedCheckboxes.length} jobs selected`
  } else {
    bulkActionsPanel.style.display = "none"
  }
}

function bulkExtendDeadlines() {
  const selectedCheckboxes = document.querySelectorAll(".job-checkbox:checked")
  const extendDays = document.getElementById("extendDays").value

  if (selectedCheckboxes.length === 0) {
    showNotification("Please select jobs to extend deadlines", "error")
    return
  }

  if (!extendDays || extendDays < 1) {
    showNotification("Please enter a valid number of days", "error")
    return
  }

  const jobIds = Array.from(selectedCheckboxes).map((cb) => cb.value)

  if (!confirm(`Extend deadlines for ${jobIds.length} jobs by ${extendDays} days?`)) {
    return
  }

  fetch("dashboard.php?action=bulk_extend_deadlines", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `job_ids=${JSON.stringify(jobIds)}&extend_days=${extendDays}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success")
        setTimeout(() => location.reload(), 1000)
      } else {
        showNotification("Failed to extend deadlines", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("An error occurred", "error")
    })
}

function exportSelectedJobs() {
  const selectedCheckboxes = document.querySelectorAll(".job-checkbox:checked")

  if (selectedCheckboxes.length === 0) {
    showNotification("Please select jobs to export", "error")
    return
  }

  const exportData = []
  const headers = ["Job Title", "Department", "Status", "Applications", "Deadline"]
  exportData.push(headers)

  selectedCheckboxes.forEach((checkbox) => {
    const row = checkbox.closest("tr")
    const cells = row.querySelectorAll("td")
    exportData.push([
      cells[1].textContent.trim(),
      cells[2].textContent.trim(),
      cells[3].textContent.trim(),
      cells[4].textContent.trim(),
      cells[5].textContent.trim(),
    ])
  })

  downloadCSV(exportData, "selected_jobs.csv")
  showNotification(`Exported ${selectedCheckboxes.length} selected jobs`, "success")
}

function clearJobSelection() {
  const checkboxes = document.querySelectorAll(".job-checkbox, #selectAllJobs")
  checkboxes.forEach((cb) => (cb.checked = false))
  updateJobBulkActions()
}

// Initialize admin dashboard
document.addEventListener("DOMContentLoaded", () => {
  // Setup search functionality
  const jobSearch = document.getElementById("jobSearch")
  if (jobSearch) {
    jobSearch.addEventListener("input", filterJobs)
  }

  const statusFilter = document.getElementById("statusFilter")
  if (statusFilter) {
    statusFilter.addEventListener("change", filterJobs)
  }

  const departmentFilter = document.getElementById("departmentFilter")
  if (departmentFilter) {
    departmentFilter.addEventListener("change", filterJobs)
  }

  // Setup bulk selection
  const applicationCheckboxes = document.querySelectorAll(".application-checkbox")
  applicationCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", updateBulkActions)
  })

  // Auto-refresh data every 30 seconds
  setInterval(() => {
    // Only refresh if user is active (has interacted recently)
    if (document.hasFocus()) {
      // You could implement AJAX refresh here
      console.log("Auto-refresh triggered")
    }
  }, 30000)
})

// Export functions
window.showTab = showTab
window.filterJobs = filterJobs
window.toggleAdvancedFilters = toggleAdvancedFilters
window.applyQuickFilter = applyQuickFilter
window.toggleSelectAll = toggleSelectAll
window.updateBulkActions = updateBulkActions
window.showBulkActions = showBulkActions
window.applyBulkAction = applyBulkAction
window.clearSelection = clearSelection
window.exportApplications = exportApplications
window.exportSelected = exportSelected
window.sortTable = sortTable
window.saveFilterPreset = saveFilterPreset
window.scheduleInterview = scheduleInterview
window.sendEmail = sendEmail
window.deleteJob = deleteJob
window.updateApplicationStatus = updateApplicationStatus
window.toggleJobBoardVisibility = toggleJobBoardVisibility
window.updateNotificationSettings = updateNotificationSettings
window.testNotification = testNotification
window.updateCVFilters = updateCVFilters
window.resetCVFilters = resetCVFilters
window.showBulkDeadlineExtension = showBulkDeadlineExtension
window.closeBulkDeadlineModal = closeBulkDeadlineModal
window.confirmBulkExtendDeadlines = confirmBulkExtendDeadlines
window.exportAllData = exportAllData
window.createSystemBackup = createSystemBackup
window.showCleanupOptions = showCleanupOptions
window.toggleSelectAllJobs = toggleSelectAllJobs
window.updateJobBulkActions = updateJobBulkActions
window.bulkExtendDeadlines = bulkExtendDeadlines
window.exportSelectedJobs = exportSelectedJobs
window.clearJobSelection = clearJobSelection
