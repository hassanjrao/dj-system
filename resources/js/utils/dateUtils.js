/**
 * Date utility functions for consistent date/time handling across the application.
 * Change the default timezone behavior here to affect all components.
 */

/**
 * Format a date string for datetime-local input
 * @param {string|Date} dateString - The date to format
 * @param {boolean} useUTC - Whether to use UTC (true) or local timezone (false)
 * @returns {string} Formatted date string (YYYY-MM-DDTHH:mm) or empty string
 */
export const formatDateTimeForInput = (dateString, useUTC = true) => {
    if (!dateString) return "";

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "";

    if (useUTC) {
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, "0");
        const day = String(date.getUTCDate()).padStart(2, "0");
        const hours = String(date.getUTCHours()).padStart(2, "0");
        const minutes = String(date.getUTCMinutes()).padStart(2, "0");
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    } else {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
};

/**
 * Format a date string for date input (YYYY-MM-DD)
 * @param {string|Date} dateString - The date to format
 * @returns {string} Formatted date string (YYYY-MM-DD) or empty string
 */
export const formatDateForInput = (dateString) => {
    if (!dateString) return "";

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "";

    return date.toISOString().split("T")[0];
};

/**
 * Get the timezone label based on current setting
 * @param {boolean} useUTC - Whether UTC is being used
 * @returns {string} Timezone label (e.g., "UTC" or "Local")
 */
export const getTimezoneLabel = (useUTC = true) => {
    return useUTC ? "UTC" : "Local";
};

// Default configuration - change this to switch timezone behavior globally
export const DATE_CONFIG = {
    useUTC: true,
};
