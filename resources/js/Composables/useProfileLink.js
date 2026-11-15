/** Where "my profile" lives for each role. */
export const profileHref = (user) => {
    if (!user) return '/login';
    if (user.is_admin) return '/admin/profile';
    if (user.is_staff) return '/staff/profile';

    return '/account/profile';
};
