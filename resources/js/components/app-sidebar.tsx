import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    FileText,
    FolderGit2,
    KeyRound,
    LayoutGrid,
    Users,
} from 'lucide-react';

import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type NavigationPermissions = {
    offices: boolean;
    services: boolean;
    eligibleEmployees: boolean;
    activationCodes: boolean;
    employeePasswordReset: boolean;
    transferCycles: boolean;
    myApplications: boolean;
    officeHeadAssignments: boolean;
    officeReviews: boolean;
    userManagement: boolean;
    districtReviews: boolean;
    mutualTransfers: boolean;
    reports: boolean;
};

type SidebarPageProps = {
    navigation: NavigationPermissions;
};

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { navigation } = usePage<SidebarPageProps>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Notifications',
            href: '/notifications',
            icon: Bell,
        },
    ];

    if (navigation?.offices) {
        mainNavItems.push({
            title: 'Office Management',
            href: '/offices',
            icon: Building2,
        });
    }

    if (navigation?.services) {
        mainNavItems.push({
            title: 'Service Management',
            href: '/services',
            icon: BriefcaseBusiness,
        });
    }

    if (navigation?.eligibleEmployees) {
        mainNavItems.push({
            title: 'Eligible Employees',
            href: '/eligible-employees',
            icon: Users,
        });
    }

    if (navigation?.activationCodes) {
        mainNavItems.push({
            title: 'Activation Codes',
            href: '/activation-codes',
            icon: KeyRound,
        });
    }

    if (navigation?.employeePasswordReset) {
        mainNavItems.push({
            title: 'Employee Password Reset',
            href: '/employee-reset-codes',
            icon: KeyRound,
        });
    }

    if (navigation?.transferCycles) {
        mainNavItems.push({
            title: 'Transfer Cycles',
            href: '/transfer-cycles',
            icon: CalendarDays,
        });
    }

    if (navigation?.myApplications) {
        mainNavItems.push({
            title: 'My Transfer Applications',
            href: '/transfer-applications',
            icon: FileText,
        });
    }

    if (navigation?.officeHeadAssignments) {
        mainNavItems.push({
            title: 'Office Head Assignments',
            href: '/office-reviewer-assignments',
            icon: Users,
        });
    }

    if (navigation?.officeReviews) {
        mainNavItems.push({
            title: 'Office Review Inbox',
            href: '/office-reviews',
            icon: FileText,
        });
    }

    if (navigation?.userManagement) {
        mainNavItems.push({
            title: 'User Management',
            href: '/user-management',
            icon: Users,
        });
    }

    if (navigation?.districtReviews) {
        mainNavItems.push({
            title: 'District Review Inbox',
            href: '/district-reviews',
            icon: FileText,
        });
    }

    if (navigation?.mutualTransfers) {
        mainNavItems.push({
            title: 'Mutual Transfer Preview',
            href: '/mutual-transfers',
            icon: Users,
        });
    }

    if (navigation?.reports) {
        mainNavItems.push({
            title: 'Management Reports',
            href: '/reports',
            icon: FileText,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}