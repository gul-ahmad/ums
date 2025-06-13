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
  },
  {
    title: 'User',
    icon: { icon: 'tabler-user' },
    children: [
      { title: 'List', to: 'user-list' },
      { title: 'View', to: { name: 'user-view-id', params: { id: 21 } } },
    ],
  },
  {
    title: 'Roles & Permissions',
    icon: { icon: 'tabler-lock' },
    children: [
      { title: 'Roles', to: 'roles' },
      // { title: 'Permissions', to: 'permissions' },
    ],
  },
]
