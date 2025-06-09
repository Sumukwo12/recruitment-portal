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

// Application filtering
function filterApplications() {
  const searchTerm = document.getElementById("applicationSearch")?.value.toLowerCase() || ""
  const rows = document.querySelectorAll("#applicationsTableBody tr")

  rows.forEach((row) => {
    const applicantName = row.querySelector(".applicant-name")?.textContent.toLowerCase() || ""
    const applicantEmail = row.querySelector(".applicant-email")?.textContent.toLowerCase() || ""
    const jobTitle = row.cells[1]?.textContent.toLowerCase() || ""

    const matches =
      applicantName.includes(searchTerm) || applicantEmail.includes(searchTerm) || jobTitle.includes(searchTerm)

    if (matches) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })
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

// Export data functionality
function exportData(type) {
  const data = []
  let filename = ""

  if (type === "jobs") {
    // Collect job data
    const rows = document.querySelectorAll("#jobsTableBody tr")
    data.push(["Job Title", "Department", "Status", "Applications", "Deadline"])

    rows.forEach((row) => {
      if (row.style.display !== "none") {
        const cells = row.querySelectorAll("td")
        data.push([
          cells[0]?.textContent.trim(),
          cells[1]?.textContent.trim(),
          cells[2]?.textContent.trim(),
          cells[3]?.textContent.trim(),
          cells[4]?.textContent.trim(),
        ])
      }
    })

    filename = "jobs_export.csv"
  } else if (type === "applications") {
    // Collect application data
    const rows = document.querySelectorAll("#applicationsTableBody tr")
    data.push(["Applicant Name", "Email", "Position", "Applied Date", "Status"])

    rows.forEach((row) => {
      if (row.style.display !== "none") {
        const cells = row.querySelectorAll("td")
        const applicantName = cells[0]?.querySelector(".applicant-name")?.textContent.trim()
        const applicantEmail = cells[0]?.querySelector(".applicant-email")?.textContent.trim()

        data.push([
          applicantName,
          applicantEmail,
          cells[1]?.textContent.trim(),
          cells[2]?.textContent.trim(),
          cells[3]?.querySelector("select")?.value || cells[3]?.textContent.trim(),
        ])
      }
    })

    filename = "applications_export.csv"
  }

  // Convert to CSV
  const csvContent = data.map((row) => row.map((cell) => `"${cell || ""}"`).join(",")).join("\n")

  // Download file
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
  const link = document.createElement("a")
  const url = URL.createObjectURL(blob)
  link.setAttribute("href", url)
  link.setAttribute("download", filename)
  link.style.visibility = "hidden"
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  showNotification("Data exported successfully", "success")
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

  const applicationSearch = document.getElementById("applicationSearch")
  if (applicationSearch) {
    applicationSearch.addEventListener("input", filterApplications)
  }

  // Setup export functionality
  const exportButton = document.querySelector('[onclick*="Export Data"]')
  if (exportButton) {
    exportButton.addEventListener("click", (e) => {
      e.preventDefault()
      const activeTab = document.querySelector(".tab-content.active")
      if (activeTab && activeTab.id === "applications-tab") {
        exportData("applications")
      } else {
        exportData("jobs")
      }
    })
  }

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
window.filterApplications = filterApplications
window.deleteJob = deleteJob
window.updateApplicationStatus = updateApplicationStatus
window.exportData = exportData
