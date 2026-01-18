<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')" aria-hidden="true"></div>
        
        <!-- Modal Content Container -->
        <div class="fixed inset-0 flex items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0 pointer-events-none">
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

          <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full pointer-events-auto" @click.stop>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ title }}
                  </h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      {{ message }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <DangerButton @click="$emit('confirm')" class="w-full sm:ml-3 sm:w-auto">
                {{ confirmText }}
              </DangerButton>
              <SecondaryButton @click="$emit('close')" class="mt-3 w-full sm:mt-0 sm:w-auto">
                Cancel
              </SecondaryButton>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import DangerButton from './DangerButton.vue';
import SecondaryButton from './SecondaryButton.vue';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirm Action',
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
});

defineEmits(['confirm', 'close']);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
