// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AJAX progress tracker for bulk messaging history page.
 *
 * @module     tool_bulkmessaging/progress_tracker
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';

const POLL_INTERVAL = 3000;
const TERMINAL_STATUSES = [2, 3, 4, 5];

/**
 * Fetch progress for a single log entry.
 *
 * @param {number} logid
 * @returns {Promise}
 */
const getProgress = (logid) => fetchMany([{
    methodname: 'tool_bulkmessaging_get_message_progress',
    args: {logid},
}])[0];

/**
 * Update the DOM for a single row.
 *
 * @param {number} logid
 * @param {object} data
 */
const updateRow = (logid, data) => {
    // Update status badge.
    const statusEl = document.getElementById('bmsg-status-' + logid);
    if (statusEl) {
        statusEl.className = data.statusclass;
        statusEl.textContent = data.statuslabel;
    }

    // Update progress bar and counts.
    const progressEl = document.getElementById('bmsg-progress-' + logid);
    if (progressEl) {
        const bar = progressEl.querySelector('.progress-bar');
        if (bar) {
            bar.style.width = data.percentage + '%';
            bar.setAttribute('aria-valuenow', data.percentage);
        }

        const countsEl = progressEl.querySelector('.bmsg-counts');
        if (countsEl) {
            let text = data.sentcount.toString();
            if (data.failedcount > 0) {
                text += ' / ' + data.failedcount + ' failed';
            }
            text += ' (' + data.percentage + '%)';
            countsEl.textContent = text;
        }

        // Show progress bar if status moved from queued to processing.
        if (data.status !== 0) {
            const progressWrap = progressEl.querySelector('.progress');
            if (progressWrap) {
                progressWrap.style.display = '';
            }
            const countsWrap = progressEl.querySelector('.bmsg-counts');
            if (countsWrap) {
                countsWrap.style.display = '';
            }
        }
    }

    // Update action icons.
    const actionsEl = document.getElementById('bmsg-actions-' + logid);
    if (actionsEl) {
        // Hide cancel button if no longer queued.
        const cancelBtn = actionsEl.querySelector('[data-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.style.display = (data.status === 0) ? '' : 'none';
        }
        // Show stop button if processing.
        const stopBtn = actionsEl.querySelector('[data-action="stop"]');
        if (stopBtn) {
            stopBtn.style.display = (data.status === 1) ? '' : 'none';
        }
        // Show start button if failed or stopped.
        const startBtn = actionsEl.querySelector('[data-action="start"]');
        if (startBtn) {
            startBtn.style.display = (data.status === 3 || data.status === 5) ? '' : 'none';
        }
        // Show delete button if terminal.
        const deleteBtn = actionsEl.querySelector('[data-action="delete"]');
        if (deleteBtn) {
            deleteBtn.style.display = TERMINAL_STATUSES.includes(data.status) ? '' : 'none';
        }
    }
};

/**
 * Start polling for a set of active log IDs.
 *
 * @param {number[]} logids
 */
const startPolling = (logids) => {
    const activeIds = new Set(logids);

    const poll = () => {
        if (activeIds.size === 0) {
            return;
        }

        const promises = [];
        for (const logid of activeIds) {
            promises.push(
                getProgress(logid)
                    .then((data) => {
                        updateRow(logid, data);
                        if (TERMINAL_STATUSES.includes(data.status)) {
                            activeIds.delete(logid);
                        }
                    })
                    .catch((err) => {
                        Log.error('Progress tracker error for log ' + logid + ': ' + err);
                        activeIds.delete(logid);
                    })
            );
        }

        Promise.all(promises).then(() => {
            if (activeIds.size > 0) {
                setTimeout(poll, POLL_INTERVAL);
            }
        });
    };

    setTimeout(poll, POLL_INTERVAL);
};

/**
 * Initialise progress tracking.
 *
 * @param {number[]} activeLogIds - Array of log IDs with status 0 or 1.
 */
export const init = (activeLogIds) => {
    if (!activeLogIds || activeLogIds.length === 0) {
        return;
    }
    startPolling(activeLogIds);
};
