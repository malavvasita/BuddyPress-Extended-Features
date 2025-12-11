/**
 * Share Script
 *
 * Handles the share button click, modal display, and AJAX requests.
 *
 * @package BuddyPressExtended
 */

(function($) {
	'use strict';

	/**
	 * Share Modal Handler
	 */
	var ShareModal = {
		modal: null,
		overlay: null,
		currentItemId: null,
		currentItemType: null,
		currentButtonNonce: null,
		friends: [],
		filteredFriends: [],
		selectedFriends: [],

		/**
		 * Initialize
		 */
		init: function() {
			this.modal = $('#bpef-share-modal');
			this.overlay = $('.bpef-modal-overlay');

			// Bind events
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			var self = this;

			// Share button click
			$(document).on('click', '.bpef-share-button', function(e) {
				e.preventDefault();
				var $button = $(this);
				self.currentItemId = $button.data('item-id');
				self.currentItemType = $button.data('item-type');
				self.currentButtonNonce = $button.data('nonce');
				self.openModal();
			});

			// Close button click
			$(document).on('click', '.bpef-modal-close, .bpef-modal-cancel, .bpef-modal-overlay', function(e) {
				e.preventDefault();
				self.closeModal();
			});

			// Send button click
			$(document).on('click', '.bpef-modal-send', function(e) {
				e.preventDefault();
				self.sendShare();
			});

			// Friend checkbox change
			$(document).on('change', '.bpef-friend-checkbox', function() {
				self.toggleFriendSelection($(this));
			});

			// Search input
			$(document).on('input', '#bpef-friend-search', function() {
				self.filterFriends($(this).val());
			});

			// Select all checkbox
			$(document).on('change', '#bpef-select-all', function() {
				self.toggleSelectAll($(this).is(':checked'));
			});

			// ESC key to close modal
			$(document).on('keydown', function(e) {
				if (e.keyCode === 27 && self.modal.is(':visible')) {
					self.closeModal();
				}
			});
		},

		/**
		 * Open modal
		 */
		openModal: function() {
			var self = this;

			// Reset state
			this.selectedFriends = [];
			this.friends = [];
			this.filteredFriends = [];

			// Show modal
			this.modal.fadeIn(300);
			$('body').addClass('bpef-modal-open');

			// Load friends
			this.loadFriends();
		},

		/**
		 * Close modal
		 */
		closeModal: function() {
			this.modal.fadeOut(300);
			$('body').removeClass('bpef-modal-open');
			this.resetModal();
		},

		/**
		 * Reset modal state
		 */
		resetModal: function() {
			this.currentItemId = null;
			this.currentItemType = null;
			this.currentButtonNonce = null;
			this.friends = [];
			this.filteredFriends = [];
			this.selectedFriends = [];
			$('#bpef-friend-search').val('');
			$('.bpef-modal-send').prop('disabled', true);
		},

		/**
		 * Load friends via AJAX
		 */
		loadFriends: function() {
			var self = this;
			var $list = $('#bpef-friends-list');

			// Show loading state
			$list.html('<div class="bpef-loading">' + bpefShare.sendingText + '</div>');

			// AJAX request
			$.ajax({
				url: bpefShare.ajaxUrl,
				type: 'POST',
				data: {
					action: 'bpef_get_friends',
					nonce: bpefShare.nonce
				},
				success: function(response) {
					if (response.success && response.data.friends) {
						self.friends = response.data.friends;
						self.filteredFriends = response.data.friends;
						self.renderFriends();
					} else {
						var message = response.data && response.data.message
							? response.data.message
							: bpefShare.errorText;
						$list.html('<div class="bpef-error">' + message + '</div>');
					}
				},
				error: function() {
					$list.html('<div class="bpef-error">' + bpefShare.errorText + '</div>');
				}
			});
		},

		/**
		 * Render friends list
		 */
		renderFriends: function() {
			var self = this;
			var $list = $('#bpef-friends-list');

			if (this.filteredFriends.length === 0) {
				$list.html('<div class="bpef-empty">' + bpefShare.selectFriend + '</div>');
				return;
			}

			var html = '<div class="bpef-select-all-wrapper">' +
				'<label><input type="checkbox" id="bpef-select-all"> ' +
				'<strong>Select All</strong></label>' +
				'</div>' +
				'<ul class="bpef-friends-list-items">';

			$.each(this.filteredFriends, function(index, friend) {
				var isSelected = self.isFriendSelected(friend.id);
				html += '<li class="bpef-friend-item' + (isSelected ? ' selected' : '') + '">' +
					'<label>' +
					'<input type="checkbox" class="bpef-friend-checkbox" value="' + friend.id + '"' +
					(isSelected ? ' checked' : '') + '>' +
					'<img src="' + friend.avatar + '" alt="' + friend.name + '" class="bpef-friend-avatar">' +
					'<span class="bpef-friend-name">' + friend.name + '</span>' +
					'</label>' +
					'</li>';
			});

			html += '</ul>';
			$list.html(html);

			// Update select all checkbox
			this.updateSelectAllCheckbox();
		},

		/**
		 * Filter friends based on search term
		 */
		filterFriends: function(searchTerm) {
			var term = searchTerm.toLowerCase().trim();
			var self = this;

			if (term === '') {
				this.filteredFriends = this.friends;
			} else {
				this.filteredFriends = this.friends.filter(function(friend) {
					return friend.name.toLowerCase().indexOf(term) !== -1;
				});
			}

			this.renderFriends();
		},

		/**
		 * Toggle friend selection
		 */
		toggleFriendSelection: function($checkbox) {
			var friendId = parseInt($checkbox.val(), 10);
			var isChecked = $checkbox.is(':checked');

			if (isChecked) {
				if (this.selectedFriends.indexOf(friendId) === -1) {
					this.selectedFriends.push(friendId);
				}
			} else {
				var index = this.selectedFriends.indexOf(friendId);
				if (index !== -1) {
					this.selectedFriends.splice(index, 1);
				}
			}

			// Update UI
			$checkbox.closest('.bpef-friend-item').toggleClass('selected', isChecked);

			// Update send button state
			this.updateSendButton();

			// Update select all checkbox
			this.updateSelectAllCheckbox();
		},

		/**
		 * Toggle select all
		 */
		toggleSelectAll: function(selectAll) {
			var self = this;

			$('.bpef-friend-checkbox').each(function() {
				var $checkbox = $(this);
				var friendId = parseInt($checkbox.val(), 10);
				var isChecked = $checkbox.is(':checked');

				if (selectAll && !isChecked) {
					$checkbox.prop('checked', true).trigger('change');
				} else if (!selectAll && isChecked) {
					$checkbox.prop('checked', false).trigger('change');
				}
			});
		},

		/**
		 * Update select all checkbox state
		 */
		updateSelectAllCheckbox: function() {
			var $checkboxes = $('.bpef-friend-checkbox');
			var checkedCount = $checkboxes.filter(':checked').length;
			var totalCount = $checkboxes.length;

			$('#bpef-select-all').prop('checked', checkedCount === totalCount && totalCount > 0);
		},

		/**
		 * Check if friend is selected
		 */
		isFriendSelected: function(friendId) {
			return this.selectedFriends.indexOf(friendId) !== -1;
		},

		/**
		 * Update send button state
		 */
		updateSendButton: function() {
			var $sendButton = $('.bpef-modal-send');
			$sendButton.prop('disabled', this.selectedFriends.length === 0);
		},

		/**
		 * Send share via AJAX
		 */
		sendShare: function() {
			var self = this;

			if (this.selectedFriends.length === 0) {
				alert(bpefShare.selectFriend);
				return;
			}

			var $sendButton = $('.bpef-modal-send');
			var originalText = $sendButton.text();
			$sendButton.prop('disabled', true).text(bpefShare.sendingText);

			// AJAX request
			$.ajax({
				url: bpefShare.ajaxUrl,
				type: 'POST',
				data: {
					action: 'bpef_send_share',
					nonce: bpefShare.nonce,
					button_nonce: this.currentButtonNonce,
					item_id: this.currentItemId,
					item_type: this.currentItemType,
					friend_ids: this.selectedFriends
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						$('#bpef-friends-list').html(
							'<div class="bpef-success">' +
							(response.data.message || bpefShare.successText) +
							'</div>'
						);

						// Close modal after delay
						setTimeout(function() {
							self.closeModal();
						}, 2000);
					} else {
						var message = response.data && response.data.message
							? response.data.message
							: bpefShare.errorText;
						alert(message);
						$sendButton.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					alert(bpefShare.errorText);
					$sendButton.prop('disabled', false).text(originalText);
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		ShareModal.init();
	});

})(jQuery);

