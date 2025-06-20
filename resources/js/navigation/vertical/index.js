export default [
  {
    title: 'Home',
    to: { name: 'root' },
    icon: { icon: 'tabler-smart-home' },
  },
  {
    title: 'Second page',
    to: { name: 'second-page' },
    icon: { icon: 'tabler-file' },
    action: 'read',    // CASL action
    subject: 'Faculty', // CASL subject (comes from Spatie perm "view faculty" via Module name)
  },
  // {
  //   title: 'User',
  //   icon: { icon: 'tabler-user' },
  //   children: [
  //     { title: 'List', to: 'user-list' },
  //     { title: 'View', to: { name: 'user-view-id', params: { id: 21 } } },
  //   ],
  // },
  {
    title: 'Roles & Permissions',
    icon: { icon: 'tabler-lock' },
    action: 'read',    // Requires ability to read...
    subject: 'Role',   // ...the 'Role' subject (from "view role" Spatie perm)
    children: [
      {
        title: 'Roles',
        to: 'roles', // Vue Router name (e.g., from pages/apps/roles.vue)
        action: 'read',   // Also requires 'read' on 'Role' to see this specific sub-link
        subject: 'Role',
      },
      // { title: 'Permissions', to: 'apps-permissions' }, // For later
    ],
  },
]
