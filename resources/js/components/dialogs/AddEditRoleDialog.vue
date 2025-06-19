<script setup>
import { computed, ref, watch } from 'vue';
import { VForm } from 'vuetify/components/VForm';

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  roleData: {
    type: Object,
    default: null,
  },
  allPermissions: {
    type: Array,
    default: () => [],
  },

})

const emit = defineEmits([
  'update:isDialogVisible',
  //'update:rolePermissions',
  'save',
])



const roleName = ref('')
const selectedPermissionIds = ref([]); // This will now store an array of selected permission IDs
const refPermissionForm = ref();
const isSelectAllPermissions = ref(false); // For the global "Select All" checkbox

// Update the "Select All" checkbox state based on selectedPermissionIds
const updateSelectAllCheckboxState = () => {
    if (props.allPermissions.length === 0) {
        isSelectAllPermissions.value = false;
        return;
    }
    const totalAvailablePermissions = props.allPermissions.flatMap(m => m.permissions.map(p => p.id)).length;
    isSelectAllPermissions.value = totalAvailablePermissions > 0 && selectedPermissionIds.value.length === totalAvailablePermissions;
};

// Populate form when dialog opens for editing or when allPermissions load
watch(() => [props.roleData, props.allPermissions], ([newRoleData, newAllPermissions]) => {
  if (newRoleData) { // Editing existing role
    roleName.value = newRoleData.name || '';
    selectedPermissionIds.value = [...(newRoleData.assigned_permission_ids || [])];
  } else { // Adding new role
    roleName.value = '';
    selectedPermissionIds.value = [];
  }
  // Check if all permissions are selected based on the current role's permissions
  updateSelectAllCheckboxState();
}, { immediate: true, deep: true });




// Watch selectedPermissionIds to update the global "Select All" checkbox
watch(selectedPermissionIds, updateSelectAllCheckboxState, { deep: true });


const closeDialog = () => {
  emit('update:isDialogVisible', false);
  // Resetting form is good, but Vuexy's onReset also set isSelectAll=false
  // Let RoleCards handle resetting roleDetailForDialog when it closes the dialog
};

const onReset = () => { // Called by DialogCloseBtn or Cancel button
  // Reset local form state
  if (props.roleData) { // If was editing
    roleName.value = props.roleData.name || '';
    selectedPermissionIds.value = [...(props.roleData.assigned_permission_ids || [])];
  } else { // If was adding
    roleName.value = '';
    selectedPermissionIds.value = [];
  }
  isSelectAllPermissions.value = false; // Reset select all
  emit('update:isDialogVisible', false); // Close the dialog
};


const onSubmit = () => {
  refPermissionForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      emit('save', {
        id: props.roleData ? props.roleData.id : undefined,
        name: roleName.value,
        permissions: selectedPermissionIds.value, // Emit array of selected permission IDs
      });
      // Don't close dialog here, let parent (RoleCards) do it on successful save
    }
  });
};


// Toggle individual permission
const togglePermission = (permissionId) => {
  const index = selectedPermissionIds.value.indexOf(permissionId);
  if (index > -1) {
    selectedPermissionIds.value.splice(index, 1);
  } else {
    selectedPermissionIds.value.push(permissionId);
  }
};

// Toggle all permissions for a specific module
const toggleModulePermissions = (module, shouldSelect) => {
  module.permissions.forEach(p => {
    const index = selectedPermissionIds.value.indexOf(p.id);
    if (shouldSelect) { // If master module checkbox is checked
      if (index === -1) selectedPermissionIds.value.push(p.id);
    } else { // If master module checkbox is unchecked
      if (index > -1) selectedPermissionIds.value.splice(index, 1);
    }
  });
};

// Check if all permissions in a module are selected
const areAllModulePermissionsSelected = (module) => {
  if (!module || !module.permissions || module.permissions.length === 0) return false;
  return module.permissions.every(p => selectedPermissionIds.value.includes(p.id));
};

// For the global "Select All" checkbox
const toggleAllPermissions = () => {
  if (isSelectAllPermissions.value) { // If it's now true (was clicked to select all)
    selectedPermissionIds.value = props.allPermissions.flatMap(m => m.permissions.map(p => p.id));
  } else { // If it's now false (was clicked to deselect all)
    selectedPermissionIds.value = [];
  }
};

// Computed property for global indeterminate state
const isGlobalIndeterminate = computed(() => {
    const totalAvailablePermissions = props.allPermissions.flatMap(m => m.permissions.map(p => p.id)).length;
    return selectedPermissionIds.value.length > 0 && selectedPermissionIds.value.length < totalAvailablePermissions;
});


</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 1100"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
    persistent
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-sm-10 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">
          {{ props.roleData && props.roleData.id ? 'Edit' : 'Add New' }} Role
        </h4>
        <p class="text-body-1 text-center mb-6">
          Set Role Permissions
        </p>

        <VForm ref="refPermissionForm" @submit.prevent="onSubmit">
          <AppTextField
            v-model="roleName"
            label="Role Name"
            placeholder="Enter Role Name"
            :rules="[v => !!v || 'Role name is required']"
            :error-messages="props.validationErrors?.name" 
            class="mb-6"
          />

          <h5 class="text-h5 my-4">
            Role Permissions
          </h5>

          <VTable class="permission-table text-no-wrap">
            <thead>
              <tr>
                <th class="text-start">
                  <span>Administrator Access</span>
                </th>
                <th colspan="3" class="text-end">
                  <VCheckbox
                    v-model="isSelectAllPermissions"
                    label="Select All"
                    :indeterminate="isGlobalIndeterminate"
                    @change="toggleAllPermissions"
                  />
                </th>
              </tr>
            </thead>
            <tbody>
              <template v-if="props.allPermissions.length > 0">
                <tr v-for="moduleGroup in props.allPermissions" :key="moduleGroup.moduleName">
                  <td class="text-capitalize font-weight-medium">
                    {{ moduleGroup.moduleName }}
                  </td>
                  <td v-for="permission in moduleGroup.permissions" :key="permission.id">
                    <VCheckbox
                      :model-value="selectedPermissionIds.includes(permission.id)"
                      :label="permission.name.replace(moduleGroup.moduleName.toLowerCase() + ' ', '').replace(moduleGroup.moduleName + ' ', '')"
                      @update:model-value="togglePermission(permission.id)"
                    />
                  </td>
                
                   <td class="text-end">
                      <VCheckbox
                        label="All"
                        :model-value="areAllModulePermissionsSelected(moduleGroup)"
                        :indeterminate="moduleGroup.permissions.some(p => selectedPermissionIds.includes(p.id)) && !areAllModulePermissionsSelected(moduleGroup)"
                        @update:model-value="toggleModulePermissions(moduleGroup, $event)"
                      />
                    </td>
                </tr>
              </template>
              <tr v-else>
                <td colspan="4" class="text-center">No permissions found.</td>
              </tr>
            </tbody>
          </VTable>

          <div class="d-flex align-center justify-center gap-4 mt-6">
            <VBtn type="submit">
              {{ props.roleData && props.roleData.id ? 'Update' : 'Submit' }}
            </VBtn>
            <VBtn color="secondary" variant="tonal" @click="onReset">
              Cancel
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    .v-checkbox {
      margin-inline-end: 8px; // Add some space between checkboxes if they are in a row
      min-inline-size: auto; // Allow checkboxes to be more compact
    }

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }

  // Ensure enough space if permissions are many per module
  td:not(:first-child, :last-child) {
    min-inline-size: 120px; // Adjust as needed
  }
}
</style>
