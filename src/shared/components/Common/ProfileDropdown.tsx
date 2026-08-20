import React, { useState } from "react";
import { Link } from "react-router-dom";
import { Dropdown, DropdownItem, DropdownMenu, DropdownToggle } from "reactstrap";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../../../features/auth/AuthProvider";

//import images
import avatar1 from "../../../assets/images/users/avatar-1.jpg";

const ProfileDropdown = () => {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    //Dropdown Toggle
    const [isProfileDropdown, setIsProfileDropdown] = useState<boolean>(false);
    const toggleProfileDropdown = () => {
        setIsProfileDropdown(!isProfileDropdown);
    };
    return (
        <React.Fragment>
            <Dropdown isOpen={isProfileDropdown} toggle={toggleProfileDropdown} className="ms-sm-3 header-item topbar-user">
                <DropdownToggle tag="button" type="button" className="btn">
                    <span className="d-flex align-items-center">
                        <img className="rounded-circle header-profile-user" src={avatar1}
                            alt="Avatar pengguna" />
                        <span className="text-start ms-xl-2">
                            <span className="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{user?.name ?? "Pengguna"}</span>
                            <span className="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">{user?.organization?.name ?? "ZETRA"}</span>
                        </span>
                    </span>
                </DropdownToggle>
                <DropdownMenu className="dropdown-menu-end">
                    <h6 className="dropdown-header">Halo {user?.name ?? "Pengguna"}!</h6>
                    <DropdownItem className='p-0'>
                        <Link to="/profile" className="dropdown-item">
                            <i className="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
                            <span className="align-middle">Profile</span>
                        </Link>
                    </DropdownItem>
                    <div className="dropdown-divider"></div>
                    <DropdownItem
                        className='dropdown-item'
                        onClick={async () => {
                            await logout();
                            navigate('/login');
                        }}
                    >
                        <i className="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                        <span className="align-middle" data-key="t-logout">Logout</span>
                    </DropdownItem>
                </DropdownMenu>
            </Dropdown>
        </React.Fragment>
    );
};

export default ProfileDropdown;
